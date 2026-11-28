package worker

import (
	"context"
	"fmt"
	"sync"

	"github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/laravel"
)

// Cliente define o contrato que o worker utiiza
// qualquer tipo que implemente essee metodo pode ser usado
type Cliente interface {
	EnviarComRetry(ctx context.Context, evento laravel.Evento, politica laravel.PoliticaRetry) (int, error)
}

// o Resultado define o processamento de um evento
type Resultado struct {
	Evento laravel.Evento
	Codigo int
	Err    error
}

// O pool controla quantos eventos sao processados simultaneamente
type Pool struct {
	cliente      Cliente
	concorrencia int
	politica     laravel.PoliticaRetry
}

// NovoPool configura o pool dos workers
func NovoPool(cliente Cliente, concorrencia int, politica laravel.PoliticaRetry) (*Pool, error) {
	if cliente == nil {
		return nil, fmt.Errorf("Cliente nao pode ser nil")
	}
	if concorrencia < 1 {
		return nil, fmt.Errorf("concorrencia deve ser maior que zero")
	}
	if politica.MaxTentativas < 1 || politica.EsperaInicial < 0 || politica.EsperaMaxima < politica.EsperaInicial {
		return nil, fmt.Errorf("politica de retry invalida")
	}
	return &Pool{
		cliente:      cliente,
		concorrencia: concorrencia,
		politica:     politica,
	}, nil
}

// Processar recebe eventos por um channel e devolve rsultados em outro channnel, Os resultados poder chegar fora de ordem..
func (p *Pool) Processar(ctx context.Context, eventos <-chan laravel.Evento) <-chan Resultado {
	resultados := make(chan Resultado)
	var wg sync.WaitGroup
	for i := 0; i < p.concorrencia; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			for {
				// agurda um evento ou cancelamento
				select {
				case <-ctx.Done():
					return
				case evento, ok := <-eventos:
					if !ok {
						return
					}
					//Evita iniciar uma operacao apos detectar cancelamento
					if ctx.Err() != nil {
						return
					}
					codigo, err := p.cliente.EnviarComRetry(ctx, evento, p.politica)
					resultado := Resultado{
						Evento: evento,
						Codigo: codigo,
						Err:    err,
					}
					// entrega o resultado ou encerra se o contexto for cancelado
					select {
					case resultados <- resultado:
					case <-ctx.Done():
						return
					}
				}
			}
		}()
	}
	//Fecha os resultados so quando todos as Goroutine terminarem
	go func() {
		wg.Wait()
		close(resultados)
	}()
	return resultados
}
