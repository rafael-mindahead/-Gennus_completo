# Gennus 2.0 — Backend Laravel

Este diretório contém o **núcleo transacional** do [Gennus 2.0](../README.md), construído em PHP 8.4 / Laravel 13, com MySQL 8.4 em Docker. O frontend legado é servido pelo Nginx a partir de `../frontend`.

> Esta é a documentação do backend do Gennus, não o template genérico do framework. Veja o [README principal](../README.md) para arquitetura, instalação e roadmap.

## Organização

~~~text
app/
├── Http/Controllers/          # Adaptadores de entrada HTTP
├── Http/Middleware/           # Autenticação de serviços
├── Models/                    # Eloquent
└── Modules/
    ├── Auth/                  # Registro/login/logout
    ├── Produtos/Actions/      # Create, Get, Update, Delete
    └── Estoque/Actions/       # RegistrarMovimentacao

routes/
├── web.php                    # Sessão, CSRF, rotas legadas /php/
└── api.php                    # API stateless interna

tests/
├── Feature/                   # Testes Laravel automatizados
└── Manual/                    # Scripts de concorrência MySQL
~~~

## Iniciar pelo Docker Compose

Os comandos abaixo devem ser executados **na raiz do repositório**.

Copie `backend/.env.example` para `backend/.env`, configure MySQL e as variáveis `DB_PASSWORD` / `DB_ROOT_PASSWORD`. Depois:

~~~bash
docker compose --env-file backend/.env up -d --build
docker compose --env-file backend/.env exec app composer install
docker compose --env-file backend/.env exec app php artisan key:generate
docker compose --env-file backend/.env up -d --force-recreate app
docker compose --env-file backend/.env exec app php artisan migrate
~~~

O sistema local é acessível em **http://localhost:8082**. Use `docker compose --env-file backend/.env ps` para consultar os serviços.

## Testes automatizados

~~~bash
docker compose --env-file backend/.env exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  -e DB_URL= \
  -e SESSION_DRIVER=array \
  -e CACHE_STORE=array \
  -e QUEUE_CONNECTION=sync \
  app php artisan test
~~~

O CI do backend reside em [`.github/workflows/backend-checks.yml`](../.github/workflows/backend-checks.yml) e executa validações estáticas de sintaxe, migrations e suíte de testes. Scripts em `tests/Manual/` são para um banco de teste separado, nunca para o banco principal.

## Contrato interno do estoque

~~~http
POST /api/internal/v1/estoque/movimentacoes
Content-Type: application/json
X-Gennus-Service-Token: <segredo de serviço>
~~~

~~~json
{
  "evento_id": "550e8400-e29b-41d4-a716-446655440000",
  "dispositivo_id": 1,
  "codigo_barras": "7900000000001",
  "quantidade_delta": 5
}
~~~

O Laravel é a autoridade sobre o estoque oficial; o Go worker e a ligação com o GenZIoT ainda estão em desenvolvimento. Não exponha tokens em arquivos versionados nem no frontend.

Consulte o [README da raiz](../README.md) para detalhes de status, rotas, segurança e prioridades.
