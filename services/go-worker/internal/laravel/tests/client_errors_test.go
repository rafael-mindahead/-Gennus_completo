package tests

import (
	"context"
	"errors"
	"net/http"
	"net/http/httptest"
	"testing"

	laravel "github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/laravel"
)

func TestClassificacaoErrosHTTP(t *testing.T) {
	cenarios := []struct {
		nome       string
		codigo     int
		temporario bool
	}{
		{"nao_autorizado", 401, false},
		{"produto_inexistente", 404, false},
		{"uuid_conflitante", 409, false},
		{"dados_invalidos", 422, false},
		{"muitas_requisicoes", 429, true},
		{"erro_interno", 500, true},
		{"servico_indisponivel", 503, true},
	}

	for _, cenario := range cenarios {
		t.Run(cenario.nome, func(t *testing.T) {
			servidor := httptest.NewServer(
				http.HandlerFunc(func(
					w http.ResponseWriter,
					r *http.Request,
				) {
					w.WriteHeader(cenario.codigo)
				}),
			)
			defer servidor.Close()

			cliente := laravel.NovoCliente(
				servidor.URL,
				"token-de-teste",
				servidor.Client(),
			)

			codigo, err := cliente.Enviar(
				context.Background(),
				laravel.Evento{
					EventoID:        "550e8400-e29b-41d4-a716-446655440000",
					DispositivoID:   1,
					CodigoBarras:    "7900000000001",
					QuantidadeDelta: 5,
				},
			)

			if codigo != cenario.codigo {
				t.Fatalf(
					"Esperado HTTP %d, recebido %d",
					cenario.codigo,
					codigo,
				)
			}

			if err == nil {
				t.Fatal("Esperava um erro HTTP")
			}

			var erroHTTP *laravel.ErroHTTP

			if !errors.As(err, &erroHTTP) {
				t.Fatalf(
					"Esperava ErroHTTP, recebido %T",
					err,
				)
			}

			if erroHTTP.Temporario() != cenario.temporario {
				t.Errorf(
					"HTTP %d: classificação incorreta",
					cenario.codigo,
				)
			}
		})
	}
}
