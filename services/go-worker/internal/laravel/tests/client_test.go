package tests

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/rafael-mindahead/-Gennus_completo/services/go-worker/internal/laravel"
)

func TestEnviarMovimentacao(t *testing.T) {
	cenarios := []struct {
		nome   string
		codigo int
	}{
		{"evento novo", http.StatusCreated},
		{"evento duplicado", http.StatusOK},
	}

	for _, cenario := range cenarios {
		t.Run(cenario.nome, func(t *testing.T) {
			servidor := httptest.NewServer(
				http.HandlerFunc(func(
					w http.ResponseWriter,
					r *http.Request,
				) {
					if r.Method != http.MethodPost {
						t.Errorf("Método inesperado: %s", r.Method)
					}

					if r.URL.Path != "/api/internal/v1/estoque/movimentacoes" {
						t.Errorf("Rota inesperada: %s", r.URL.Path)
					}

					if r.Header.Get("X-Gennus-Service-Token") != "token-de-teste" {
						t.Error("Token de serviço ausente")
					}

					if r.Header.Get("Content-Type") != "application/json" {
						t.Error("Content-Type incorreto")
					}

					var evento laravel.Evento

					if err := json.NewDecoder(r.Body).Decode(&evento); err != nil {
						t.Errorf("JSON inválido: %v", err)
					}

					if evento.CodigoBarras != "7900000000001" {
						t.Error("Código de barras incorreto")
					}

					if evento.QuantidadeDelta != 5 {
						t.Error("Quantidade incorreta")
					}

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

			if err != nil {
				t.Fatalf("Erro inesperado: %v", err)
			}

			if codigo != cenario.codigo {
				t.Errorf(
					"Esperado HTTP %d, recebido %d",
					cenario.codigo,
					codigo,
				)
			}
		})
	}
}
