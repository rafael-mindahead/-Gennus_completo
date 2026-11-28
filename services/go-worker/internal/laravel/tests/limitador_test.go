package tests

import (
	"context"
	"errors"
	"net/http"
	"net/http/httptest"
	"sort"
	"sync"
	"testing"
	"time"

	laravel "github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/laravel"
)

func TestLimitadorCompartilhado(t *testing.T) {
	var mu sync.Mutex
	var horarios []time.Time

	servidor := httptest.NewServer(
		http.HandlerFunc(func(
			w http.ResponseWriter,
			r *http.Request,
		) {
			mu.Lock()
			horarios = append(horarios, time.Now())
			mu.Unlock()

			w.WriteHeader(http.StatusCreated)
		}),
	)
	defer servidor.Close()

	// Intervalo curto apenas para os testes.
	limitador, err := laravel.NovoLimitadorTaxa(
		50 * time.Millisecond,
	)
	if err != nil {
		t.Fatal(err)
	}

	cliente := laravel.NovoClienteComLimitador(
		servidor.URL,
		"token-de-teste",
		servidor.Client(),
		limitador,
	)

	var wg sync.WaitGroup
	resultados := make(chan error, 3)
	iniciar := make(chan struct{})

	for i := 0; i < 3; i++ {
		wg.Add(1)

		go func() {
			defer wg.Done()
			<-iniciar

			_, err := cliente.Enviar(
				context.Background(),
				laravel.Evento{
					EventoID:        "550e8400-e29b-41d4-a716-446655440000",
					DispositivoID:   1,
					CodigoBarras:    "7900000000001",
					QuantidadeDelta: 1,
				},
			)

			resultados <- err
		}()
	}

	close(iniciar)
	wg.Wait()
	close(resultados)

	for err := range resultados {
		if err != nil {
			t.Fatal(err)
		}
	}

	mu.Lock()
	defer mu.Unlock()

	if len(horarios) != 3 {
		t.Fatalf(
			"Esperava 3 requisicoes, recebeu %d",
			len(horarios),
		)
	}

	sort.Slice(horarios, func(i, j int) bool {
		return horarios[i].Before(horarios[j])
	})

	for i := 1; i < len(horarios); i++ {
		intervalo := horarios[i].Sub(horarios[i-1])

		if intervalo < 30*time.Millisecond {
			t.Errorf(
				"Requisicoes muito proximas: %v",
				intervalo,
			)
		}
	}
}

func TestLimitadorRespeitaCancelamento(t *testing.T) {
	limitador, err := laravel.NovoLimitadorTaxa(
		300 * time.Millisecond,
	)
	if err != nil {
		t.Fatal(err)
	}

	// Consome a primeira permissao.
	if err := limitador.Aguardar(
		context.Background(),
	); err != nil {
		t.Fatal(err)
	}

	ctx, cancelar := context.WithTimeout(
		context.Background(),
		25*time.Millisecond,
	)
	defer cancelar()

	// A segunda permissao nao deve ser liberada
	// antes do cancelamento.
	err = limitador.Aguardar(ctx)

	if !errors.Is(err, context.DeadlineExceeded) {
		t.Fatalf(
			"Esperava cancelamento, recebeu %v",
			err,
		)
	}
}

func TestRetryTambemRespeitaLimitador(t *testing.T) {
	var horarios []time.Time

	servidor := httptest.NewServer(
		http.HandlerFunc(func(
			w http.ResponseWriter,
			r *http.Request,
		) {
			horarios = append(horarios, time.Now())

			if len(horarios) == 1 {
				w.WriteHeader(http.StatusServiceUnavailable)
				return
			}

			w.WriteHeader(http.StatusCreated)
		}),
	)
	defer servidor.Close()

	limitador, err := laravel.NovoLimitadorTaxa(
		50 * time.Millisecond,
	)
	if err != nil {
		t.Fatal(err)
	}

	cliente := laravel.NovoClienteComLimitador(
		servidor.URL,
		"token-de-teste",
		servidor.Client(),
		limitador,
	)

	codigo, err := cliente.EnviarComRetry(
		context.Background(),
		laravel.Evento{
			EventoID:        "550e8400-e29b-41d4-a716-446655440000",
			DispositivoID:   1,
			CodigoBarras:    "7900000000001",
			QuantidadeDelta: 5,
		},
		laravel.PoliticaRetry{
			MaxTentativas: 2,
			EsperaInicial: time.Millisecond,
			EsperaMaxima:  time.Millisecond,
		},
	)

	if err != nil || codigo != http.StatusCreated {
		t.Fatalf(
			"Esperava sucesso apos retry: %d, %v",
			codigo,
			err,
		)
	}

	if len(horarios) != 2 {
		t.Fatalf(
			"Esperava 2 tentativas, recebeu %d",
			len(horarios),
		)
	}

	intervalo := horarios[1].Sub(horarios[0])

	if intervalo < 30*time.Millisecond {
		t.Errorf(
			"Retry ignorou rate limiter: %v",
			intervalo,
		)
	}
}
