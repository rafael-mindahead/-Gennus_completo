package tests

import (
	"context"
	"fmt"
	"testing"
	"time"

	laravel "github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/laravel"
	worker "github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/worker"
)

func TestPoolEncerraSemPerderEventos(t *testing.T) {
	ctx, cancelar := context.WithTimeout(
		context.Background(),
		2*time.Second,
	)
	defer cancelar()

	cliente := &clienteSimulado{}

	pool, err := worker.NovoPool(
		cliente,
		2,
		politicaTeste(),
	)
	if err != nil {
		t.Fatal(err)
	}

	eventos := make(chan laravel.Evento, 2)

	// Produtor de eventos.
	go func() {
		defer close(eventos)

		for i := 1; i <= 8; i++ {
			evento := laravel.Evento{
				EventoID: fmt.Sprintf(
					"00000000-0000-4000-8000-%012d",
					i,
				),
				DispositivoID:   1,
				CodigoBarras:    "7900000000001",
				QuantidadeDelta: 1,
			}

			select {
			case eventos <- evento:
			case <-ctx.Done():
				return
			}
		}
	}()

	resultados := pool.Processar(ctx, eventos)

	recebidos := make(map[string]bool)

	for {
		select {
		case resultado, ok := <-resultados:
			if !ok {
				if len(recebidos) != 8 {
					t.Fatalf(
						"Esperava 8 resultados, recebeu %d",
						len(recebidos),
					)
				}
				return
			}

			id := resultado.Evento.EventoID

			if recebidos[id] {
				t.Fatalf("Evento duplicado: %s", id)
			}

			recebidos[id] = true

		case <-ctx.Done():
			t.Fatal(
				"Timeout: pool nao encerrou corretamente",
			)
		}
	}
}
