package laravel

import (
	"context"
	"fmt"
	"time"
)

// LimitadorTaxa ontrola o intervalo minimo entre duas requisicoes HTTP
type LimitadorTaxa struct {
	intervalo time.Duration
	turno     chan struct{}
	proxima   time.Time
}

// NovoLimitadorTaxa cria um limitador compartilhado
func NovoLimitadorTaxa(intervalo time.Duration) (*LimitadorTaxa, error) {
	if intervalo <= 0 {
		return nil, fmt.Errorf("intervalo deve ser maior que zero")
	}
	limitador := &LimitadorTaxa{
		intervalo: intervalo,
		turno:     make(chan struct{}, 1),
	}
	// A primeira requisicao pode passar imediatamente
	limitador.turno <- struct{}{}
	return limitador, nil
}

// Aguardar espera ate que uma nova requisicao possa ser iniciado respeitando o contexto
func (l *LimitadorTaxa) Aguardar(ctx context.Context) error {
	if err := ctx.Err(); err != nil {
		return err
	}
	// apenas uma goroutine controla o relogio do limitador por vez
	select {
	case <-ctx.Done():
		return ctx.Err()
	case <-l.turno:
	}
	defer func() {
		l.turno <- struct{}{}
	}()
	if err := ctx.Err(); err != nil {
		return err
	}
	espera := time.Until(l.proxima)

	if espera > 0 {
		timer := time.NewTimer(espera)
		defer timer.Stop()

		select {
		case <-ctx.Done():
			return ctx.Err()
		case <-timer.C:
		}
	}
	if err := ctx.Err(); err != nil {
		return err
	}
	// Reserva o proximo instante permetido
	l.proxima = time.Now().Add(l.intervalo)
	return nil
}
