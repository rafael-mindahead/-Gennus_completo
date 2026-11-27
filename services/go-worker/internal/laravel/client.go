package laravel

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"strings"
	"time"
)

// enviada ao laravel para registrar uma movimentaçao de estoque
type Evento struct {
	EventoID        string `json:"evento_id"`
	DispositivoID   int64  `json:"dispositivo_id"`
	CodigoBarras    string `json:"codigo_barras"`
	QuantidadeDelta int    `json:"quantidade_delta"`
}

// Cliente realiza a comunicacao http com o laravel
type Cliente struct {
	baseURL    string
	token      string
	httpClient *http.Client
}

// Novo cliente configura o cliente de integracao
func NovoCliente(baseURL string, token string, httpClient *http.Client) *Cliente {
	if httpClient == nil {
		httpClient = &http.Client{
			Timeout: 10 * time.Second,
		}
	}
	return &Cliente{
		baseURL:    strings.TrimRight(baseURL, "/"),
		token:      token,
		httpClient: httpClient,
	}
}

// Enviar trasmite um evento para o laravel e retorn o status code da resposta
func (c *Cliente) Enviar(ctx context.Context, evento Evento) (int, error) {
	corpo, err := json.Marshal(evento) // Marshal transforma o evento em json
	if err != nil {
		return 0, fmt.Errorf("erro ao serializar evento: %w", err)
	}
	ctx, cancelar := context.WithTimeout(ctx, 10*time.Second)
	defer cancelar()
	url := c.baseURL + "/api/internal/v1/estoque/movimentacoes"
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, url, bytes.NewReader(corpo))
	if err != nil {
		return 0, fmt.Errorf("erro ao criar requesicao: %w", err)
	}
	req.Header.Set("Content-type", "application/json")
	req.Header.Set("X-Gennus-Service-Token", c.token)
	resp, err := c.httpClient.Do(req)
	if err != nil {
		return 0, fmt.Errorf("erro de comunicao com laravel: %w", err)
	}
	defer resp.Body.Close()
	switch resp.StatusCode {
	case http.StatusOK, http.StatusCreated, http.StatusAccepted:
		return resp.StatusCode, nil
	default:
		return resp.StatusCode, fmt.Errorf("Laravel retornou http %d", resp.StatusCode)
	}
}
