package tests

import (
	"context"
	"errors"
	"testing"
	"time"

	laravel "github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/laravel"
	worker "github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/worker"
)

// Simula uma operação que permanece ativa
// até receber o cancelamento do contexto.
type clienteCancelamento struct {
	iniciou chan struct{}
}

func (c *clienteCancelamento) EnviarComRetry(
	ctx context.Context,
	evento laravel.Evento,
	politica laravel.PoliticaRetry,
) (int, error) {
	close(c.iniciou)

	<-ctx.Done()

	return 0, ctx.Err()
}

func TestPoolPreservaResultadoAoCancelar(t *testing.T) {
	ctx, cancelar := context.WithCancel(
		context.Background(),
	)
	defer cancelar()

	cliente := &clienteCancelamento{
		iniciou: make(chan struct{}),
	}

	pool, err := worker.NovoPool(
		cliente,
		1,
		laravel.PoliticaRetry{
			MaxTentativas: 3,
			EsperaInicial: time.Millisecond,
			EsperaMaxima:  5 * time.Millisecond,
		},
	)
	if err != nil {
		t.Fatal(err)
	}

	evento := laravel.Evento{
		EventoID:        "550e8400-e29b-41d4-a716-446655440000",
		DispositivoID:   1,
		CodigoBarras:    "7900000000001",
		QuantidadeDelta: 5,
	}

	eventos := make(chan laravel.Evento, 1)
	eventos <- evento
	close(eventos)

	resultados := pool.Processar(ctx, eventos)

	// Aguarda o worker começar a operação.
	select {
	case <-cliente.iniciou:
	case <-time.After(time.Second):
		t.Fatal("Worker nao iniciou o evento")
	}

	// Simula o encerramento do serviço.
	cancelar()

	// Mantém o consumidor ausente por um instante
	// para detectar descarte do resultado.
	time.Sleep(30 * time.Millisecond)

	select {
	case resultado, ok := <-resultados:
		if !ok {
			t.Fatal(
				"Resultado descartado no cancelamento",
			)
		}

		if resultado.Evento.EventoID != evento.EventoID {
			t.Fatal("UUID do resultado incorreto")
		}

		if !errors.Is(
			resultado.Err,
			context.Canceled,
		) {
			t.Fatalf(
				"Esperava context.Canceled, recebeu %v",
				resultado.Err,
			)
		}

	case <-time.After(time.Second):
		t.Fatal("Worker nao entregou o resultado")
	}

	// O canal deve fechar após os workers saírem.
	select {
	case _, ok := <-resultados:
		if ok {
			t.Fatal("Resultado inesperado")
		}

	case <-time.After(time.Second):
		t.Fatal("Pool nao encerrou corretamente")
	}
}
