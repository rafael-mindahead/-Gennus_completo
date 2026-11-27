package tests

import (
	"context"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	laravel "github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/laravel"
)

func TestEnviarComRetry(t *testing.T) {
	cenarios := []struct {
		nome       string
		respostas  []int
		tentativas int
		sucesso    bool
	}{
		{
			"recupera_apos_503",
			[]int{503, 503, 201},
			3,
			true,
		},
		{
			"recupera_apos_429",
			[]int{429, 200},
			2,
			true,
		},
		{
			"nao_repete_422",
			[]int{422},
			1,
			false,
		},
		{
			"nao_repete_401",
			[]int{401},
			1,
			false,
		},
		{
			"esgota_tentativas",
			[]int{503, 503, 503},
			3,
			false,
		},
	}

	for _, cenario := range cenarios {
		t.Run(cenario.nome, func(t *testing.T) {
			contador := 0

			servidor := httptest.NewServer(
				http.HandlerFunc(func(
					w http.ResponseWriter,
					r *http.Request,
				) {
					contador++

					indice := contador - 1

					if indice >= len(cenario.respostas) {
						indice = len(cenario.respostas) - 1
					}

					w.WriteHeader(cenario.respostas[indice])
				}),
			)
			defer servidor.Close()

			cliente := laravel.NovoCliente(
				servidor.URL,
				"token-de-teste",
				servidor.Client(),
			)

			evento := laravel.Evento{
				EventoID:        "550e8400-e29b-41d4-a716-446655440000",
				DispositivoID:   1,
				CodigoBarras:    "7900000000001",
				QuantidadeDelta: 5,
			}

			_, err := cliente.EnviarComRetry(
				context.Background(),
				evento,
				laravel.PoliticaRetry{
					MaxTentativas: 3,
					EsperaInicial: time.Millisecond,
					EsperaMaxima:  5 * time.Millisecond,
				},
			)

			if contador != cenario.tentativas {
				t.Errorf(
					"Esperava %d tentativas, recebeu %d",
					cenario.tentativas,
					contador,
				)
			}

			if cenario.sucesso && err != nil {
				t.Errorf("Esperava sucesso: %v", err)
			}

			if !cenario.sucesso && err == nil {
				t.Error("Esperava um erro")
			}
		})
	}
}
