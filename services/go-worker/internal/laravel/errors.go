package laravel

import (
	"fmt"
	"net/http"
)

// ErroHTTP representa um erro http retornado pelo laravel
type ErroHTTP struct {
	Codigo int
}

// Error implementa a interface error do GO
func (e *ErroHTTP) Error() string {
	return fmt.Sprintf("Laravel retornou HTTP %d", e.Codigo)
}

// Temporario indica se o erro e temporario, se a requisicao pode ser repetida ou nao
func (e *ErroHTTP) Temporario() bool {
	return e.Codigo == http.StatusTooManyRequests || (e.Codigo >= 500 && e.Codigo < 599)
}
