package tests

import (
	"context"
	"fmt"
	"net/http"
	"sync"
	"testing"
	"time"

	laravel "github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/laravel"
	worker "github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/worker"
)

// Simula o cliente Laravel para testar concorrência.
type clienteSimulado struct {
	mu        sync.Mutex
	ativos    int
	maxAtivos int
}

func (c *clienteSimulado) EnviarComRetry(
	ctx context.Context,
	evento laravel.Evento,
	politica laravel.PoliticaRetry,
) (int, error) {
	c.mu.Lock()
	c.ativos++

	if c.ativos > c.maxAtivos {
		c.maxAtivos = c.ativos
	}
	c.mu.Unlock()

	defer func() {
		c.mu.Lock()
		c.ativos--
		c.mu.Unlock()
	}()

	// Simula o tempo de processamento.
	select {
	case <-time.After(10 * time.Millisecond):
	case <-ctx.Done():
		return 0, ctx.Err()
	}

	// Simula um evento rejeitado pelo Laravel.
	if evento.EventoID ==
		"00000000-0000-4000-8000-000000000003" {
		return http.StatusUnprocessableEntity,
			&laravel.ErroHTTP{
				Codigo: http.StatusUnprocessableEntity,
			}
	}

	return http.StatusCreated, nil
}

func politicaTeste() laravel.PoliticaRetry {
	return laravel.PoliticaRetry{
		MaxTentativas: 3,
		EsperaInicial: time.Millisecond,
		EsperaMaxima:  5 * time.Millisecond,
	}
}

func TestPoolLimitaConcorrencia(t *testing.T) {
	cliente := &clienteSimulado{}

	pool, err := worker.NovoPool(
		cliente,
		2,
		politicaTeste(),
	)
	if err != nil {
		t.Fatal(err)
	}

	eventos := make(chan laravel.Evento, 8)

	for i := 1; i <= 8; i++ {
		eventos <- laravel.Evento{
			EventoID: fmt.Sprintf(
				"00000000-0000-4000-8000-%012d", i,
			),
			DispositivoID:   1,
			CodigoBarras:    "7900000000001",
			QuantidadeDelta: 1,
		}
	}
	close(eventos)

	recebidos := make(map[string]bool)
	falhas := 0

	for resultado := range pool.Processar(
		context.Background(),
		eventos,
	) {
		id := resultado.Evento.EventoID

		if recebidos[id] {
			t.Fatalf("Evento duplicado: %s", id)
		}
		recebidos[id] = true

		if resultado.Err != nil {
			falhas++

			if resultado.Codigo != 422 {
				t.Errorf(
					"Código de erro inesperado: %d",
					resultado.Codigo,
				)
			}
		}
	}

	if len(recebidos) != 8 {
		t.Errorf(
			"Esperava 8 resultados, recebeu %d",
			len(recebidos),
		)
	}

	if falhas != 1 {
		t.Errorf(
			"Esperava 1 falha, recebeu %d",
			falhas,
		)
	}

	cliente.mu.Lock()
	maximo := cliente.maxAtivos
	cliente.mu.Unlock()

	if maximo > 2 {
		t.Errorf(
			"Concorrência ultrapassou limite: %d",
			maximo,
		)
	}

	if maximo < 2 {
		t.Errorf(
			"Esperava processamento simultâneo; máximo: %d",
			maximo,
		)
	}
}

func TestPoolRejeitaConcorrenciaInvalida(
	t *testing.T,
) {
	cliente := &clienteSimulado{}

	_, err := worker.NovoPool(
		cliente,
		0,
		politicaTeste(),
	)

	if err == nil {
		t.Fatal(
			"Pool não deve aceitar zero workers",
		)
	}
}
