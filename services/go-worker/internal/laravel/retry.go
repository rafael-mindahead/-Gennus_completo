package laravel

import (
	"context"
	"errors"
	"fmt"
	"time"
)

// PoliticaRetry define quantas vezes tentar
// e quanto esperar entre cada tentativa\
type PoliticaRetry struct {
	MaxTentativas int
	EsperaInicial time.Duration
	EsperaMaxima  time.Duration
}

// EnviarComRetry envia um evento para o laravel, repetindo a requisicao caso ocorra um erro temporario
func (c *Cliente) EnviarComRetry(ctx context.Context, evento Evento, politica PoliticaRetry) (int, error) {
	if politica.MaxTentativas < 1 || politica.EsperaInicial < 0 || politica.EsperaMaxima < politica.EsperaInicial {
		return 0, fmt.Errorf("politica de retry invalida")
	}
	espera := politica.EsperaInicial
	for tentativa := 1; ; tentativa++ {
		// Interrompe a repeticao se o contexto for cancelado
		if err := ctx.Err(); err != nil {
			return 0, err
		}
		// mantem o mesmo evento e o mesmo UUID
		codigo, err := c.Enviar(ctx, evento)
		if err == nil {
			return codigo, nil
		}
		if tentativa >= politica.MaxTentativas {
			return codigo, err
		}
		var erroHTTP *ErroHTTP
		if !errors.As(err, &erroHTTP) {
			return codigo, err
		}
		if !erroHTTP.Temporario() {
			return codigo, err
		}
		//espera sem impedir o cancelamento
		timer := time.NewTimer(espera)

		select {
		case <-ctx.Done():
			timer.Stop()
			return codigo, ctx.Err()
		case <-timer.C:
			//tempo de espera expirou, continu a repeticao
		}
		// Backoff exponencial limitado
		if espera >= politica.EsperaMaxima/2 {
			espera = politica.EsperaMaxima
		} else {
			espera *= 2
		}
	}
}
