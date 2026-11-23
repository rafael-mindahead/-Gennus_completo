# Gennus 2.0

**ERP em reconstrução, com foco em engenharia de software, integridade de estoque, testes e evolução para um ecossistema distribuído.**

[![Backend checks](https://github.com/rafael-mindahead/-Gennus_completo/actions/workflows/backend-checks.yml/badge.svg?branch=develop%2Fgennus-2)](https://github.com/rafael-mindahead/-Gennus_completo/actions/workflows/backend-checks.yml)

> **Status:** backend em desenvolvimento. A interface HTML/CSS/JS antiga foi retirada da branch `develop/gennus-2` para abrir espaço à futura implementação em React + TypeScript. CRUD de produtos, autenticação e o motor inicial de estoque estão implementados no Laravel; Go, React e Python/ML continuam planejados, sem serviços em produção.

## Objetivo

O Gennus 2.0 refatora um ERP originalmente desenvolvido em PHP para uma base Laravel modular. Busca manter o estoque oficial sob **uma única autoridade transacional** e, no futuro, integrar eventos físicos originados no projeto independente **GenZIoT**.

## Stack atual

| Camada | Tecnologias |
| --- | --- |
| Backend | PHP 8.4, Laravel 13, Eloquent, Actions |
| Banco principal | MySQL 8.4 (InnoDB) |
| Desenvolvimento/testes | SQLite em memória nos testes PHPUnit; MySQL em testes manuais de concorrência |
| Interface | React + TypeScript (planejado; sem frontend operacional nesta branch) |
| Infraestrutura local | Docker Compose, Nginx, PHP-FPM |
| Qualidade | PHPUnit/Feature e GitHub Actions do backend |

## Arquitetura

~~~mermaid
flowchart TD
    R["React + TypeScript (planejado)"] -. "UI futura" .-> N["Nginx: rotas HTTP preservadas"]
    N --> L["Laravel: autenticação, Actions e contratos HTTP"]
    L --> DB[("MySQL: estoque oficial e movimentações")]

    I["GenZIoT: ESP32 + Rust (projeto separado)"] -. "integração futura" .-> G["Go worker (planejado)"]
    G -. "API interna já criada" .-> L
    P["Python: dados e ML (planejado)"] -. "integração futura" .-> L
~~~

**Regras arquiteturais:**

- Laravel valida operações de negócio e atualiza o estoque oficial em transações; o Go não deve escrever diretamente no banco.
- Chaves internas de produto, movimentação e demais entidades usam **BIGINT**; eventos distribuídos usam **UUID** e restrição de unicidade para idempotência.
- Quantidades de estoque usam `DECIMAL(12,3)`; custo e preço dos produtos, `DECIMAL(12,2)`. As regras para valores monetários de vendas/financeiro ainda precisam ser definidas por domínio.
- A integração IoT inicial aceita produtos com unidade `un` e `quantidade_delta` inteira. Unidades `kg` e `L` ainda não estão integradas ao fluxo IoT.

## Estrutura do repositório

~~~text
.
├── .github/workflows/          # CI do backend
├── backend/
│   ├── app/
│   │   ├── Http/               # Controllers e middleware
│   │   ├── Models/
│   │   └── Modules/            # Auth, Produtos, Estoque (Actions)
│   ├── database/migrations/
│   ├── routes/                 # web.php e api.php
│   └── tests/                  # Feature e testes manuais MySQL
├── frontend/                   # Pasta reservada para o React; contém apenas README
├── infra/docker/               # PHP-FPM e Nginx
├── docs/
└── compose.yaml
~~~

## Como executar localmente

**Pré-requisitos:** Git, Docker Desktop com Compose e uma porta `8082` livre.

1. Copie `backend/.env.example` para `backend/.env`.
2. Configure `APP_KEY` (gerada abaixo) e o banco no arquivo `backend/.env`. O Compose precisa de `DB_PASSWORD` e `DB_ROOT_PASSWORD` definidos. Para rodar dentro dos containers, utilize:

~~~dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8082
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=gennus_v2
DB_USERNAME=gennus
DB_PASSWORD=defina-uma-senha-local-forte
DB_ROOT_PASSWORD=defina-outra-senha-local-forte
SESSION_DRIVER=database
~~~

**Não copie essas senhas de exemplo para produção. Nunca comite `backend/.env`.**

3. Na **raiz do repositório**, inicie os contêineres e instale dependências:

~~~bash
docker compose --env-file backend/.env up -d --build
docker compose --env-file backend/.env exec app composer install
docker compose --env-file backend/.env exec app php artisan key:generate
~~~

4. Recrie o contêiner da aplicação após gerar a chave (o Compose injeta variáveis do `.env` na inicialização), depois aplique as migrations:

~~~bash
docker compose --env-file backend/.env up -d --force-recreate app
docker compose --env-file backend/.env exec app php artisan migrate
docker compose --env-file backend/.env ps
~~~

5. Para verificar o backend, acesse **http://localhost:8082/up** (health check). O Nginx continua encaminhando `/php/*` e `/api/*` ao Laravel. **A rota `/` não tem interface nesta fase e deve retornar 404**, até a chegada do React.

Para desligar os serviços sem excluir dados:

~~~bash
docker compose --env-file backend/.env down
~~~

> **Atenção:** não execute `docker compose down -v` sem intenção explícita de apagar volumes, nem `migrate:fresh` no banco principal.

## Funcionalidades implementadas

- Registro, login, sessão e logout com autenticação Laravel e CSRF nas rotas web; no momento, sem interface HTML. A nova SPA precisará ser integrada/testada posteriormente.
- Produtos: Actions independentes de criação, consulta, edição e arquivamento lógico (Soft Delete), com validações e código de barras no backend.
- Estoque: registro de movimentação via transação, `lockForUpdate()`, evento UUID único e rejeição de saldo negativo.
- API interna inicial para recebimento de eventos do GenZIoT por token de serviço, **ainda sem worker Go conectado**.
- Testes HTTP e de domínio e pipeline de CI do backend.

### Rotas principais

| Método | Rota | Uso |
| --- | --- | --- |
| GET | `/php/csrf.php` | Fornece token CSRF |
| POST | `/php/usuario_novo.php` | Cadastro de usuário |
| POST | `/php/usuario_login.php` | Login |
| GET | `/php/valida_sessao.php` | Consulta sessão |
| POST | `/php/cliente_logoff.php` | Logout |
| GET | `/php/produto_get.php` | Lista ou consulta produto por `?id=` |
| POST | `/php/produto_novo.php` | Cria produto |
| POST | `/php/produto_alterar.php?id=...` | Atualiza produto |
| POST | `/php/produto_excluir.php?id=...` | Arquiva produto |
| POST | `/api/internal/v1/estoque/movimentacoes` | Registra evento IoT, protegido por token de serviço |

**Contrato de evento (API interna):**

~~~json
{
  "evento_id": "550e8400-e29b-41d4-a716-446655440000",
  "dispositivo_id": 1,
  "codigo_barras": "7900000000001",
  "quantidade_delta": 5
}
~~~

O cliente de serviço envia `Content-Type: application/json` e `X-Gennus-Service-Token`. A credencial deve ser configurada no backend como `GENZIOT_INTERNAL_TOKEN`, nunca no JavaScript do navegador. O endpoint retorna `201` na criação, `200` para duplicata idêntica, `401` sem autenticação, `404` quando o produto não existe, `409` em conflito de UUID e `422` para erro de validação.

## Testes e CI

A partir da raiz do repositório:

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

O workflow `.github/workflows/backend-checks.yml` executa verificações do Laravel. O workflow e os testes exclusivos do frontend antigo foram retirados. Os scripts de concorrência em `backend/tests/Manual/` exigem **MySQL de testes isolado** (por exemplo, `gennus_v2_test`); ainda não são um gate de CI.

## Design da próxima interface

A identidade visual roxo/preto do Gennus, com decisões sobre tipografia, sidebar, cards e gráficos, está documentada em [`docs/DESIGN_SYSTEM.md`](docs/DESIGN_SYSTEM.md). Os arquivos HTML/CSS/JS anteriores permanecem recuperáveis no [commit anterior à limpeza](https://github.com/rafael-mindahead/-Gennus_completo/tree/3e90488311aa05a22e27c901e64f433c2964d2db/frontend); não são necessários para executar o backend.

## Próximas prioridades

1. Reforçar autenticação, autorização RBAC, CSRF/sessões e contratos HTTP do Laravel para clientes futuros.
2. Automatizar os testes concorrentes em MySQL/InnoDB em uma base isolada.
3. Integrar GenZIoT por **worker Go**, com limites de concorrência, backpressure, retentativas e rastreabilidade.
4. Desenvolver uma interface nova em React + TypeScript a partir do design registrado, sem reaproveitar os módulos HTML/JS antigos.
5. Implementar análises e Python/ML somente quando houver dados e casos de uso validados.

## Desenvolvimento

- Branch de trabalho: `develop/gennus-2`; `main` é atualizada após validação e merge.
- Preferir mudanças pequenas, testes antes de merge e commits descritivos.
- A interface legada foi descontinuada nesta branch; as telas históricas de vendas/financeiro **não comprovam** a existência de APIs transacionais completas.
- Para bugs ou melhorias, utilize as issues e o histórico de Pull Requests do repositório.

**Status:** projeto em evolução; não é uma distribuição pronta para produção.
