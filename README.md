# Gennus 2.0

**ERP em reconstrução, com foco em engenharia de software, integridade de estoque, testes e evolução para um ecossistema distribuído.**

[![Backend checks](https://github.com/rafael-mindahead/-Gennus_completo/actions/workflows/backend-checks.yml/badge.svg?branch=develop%2Fgennus-2)](https://github.com/rafael-mindahead/-Gennus_completo/actions/workflows/backend-checks.yml)
[![Frontend checks](https://github.com/rafael-mindahead/-Gennus_completo/actions/workflows/frontend-checks.yml/badge.svg?branch=develop%2Fgennus-2)](https://github.com/rafael-mindahead/-Gennus_completo/actions/workflows/frontend-checks.yml)

> **Status:** em desenvolvimento. O CRUD de produtos, a autenticação e o motor inicial de movimentações estão implementados. As páginas legadas de vendas/financeiro não representam, por si, módulos backend concluídos. Go, React e Python/ML são evoluções planejadas, não serviços em produção.

## Objetivo

O Gennus 2.0 refatora um ERP originalmente desenvolvido em PHP para uma base Laravel modular. Busca manter o estoque oficial sob **uma única autoridade transacional** e, no futuro, integrar eventos físicos originados no projeto independente **GenZIoT**.

## Stack atual

| Camada | Tecnologias |
| --- | --- |
| Backend | PHP 8.4, Laravel 13, Eloquent, Actions |
| Banco principal | MySQL 8.4 (InnoDB) |
| Desenvolvimento/testes | SQLite em memória nos testes PHPUnit; MySQL em testes manuais de concorrência |
| Web | HTML, CSS e JavaScript (frontend legado) |
| Infraestrutura local | Docker Compose, Nginx, PHP-FPM |
| Qualidade | PHPUnit/Feature, testes de frontend, GitHub Actions |

## Arquitetura

~~~mermaid
flowchart TD
    U["Navegador: HTML / CSS / JS"] --> N["Nginx"]
    N --> L["Laravel: autenticação, Actions e contratos HTTP"]
    L --> DB[("MySQL: estoque oficial e movimentações")]

    I["GenZIoT: ESP32 + Rust (projeto separado)"] -. "integração futura" .-> G["Go worker (planejado)"]
    G -. "API interna já criada" .-> L
    R["React + TypeScript (planejado)"] -.-> N
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
├── .github/workflows/          # CI de backend e frontend
├── backend/
│   ├── app/
│   │   ├── Http/               # Controllers e middleware
│   │   ├── Models/
│   │   └── Modules/            # Auth, Produtos, Estoque (Actions)
│   ├── database/migrations/
│   ├── routes/                 # web.php e api.php
│   └── tests/                  # Feature e testes manuais MySQL
├── frontend/                   # Interface atual, páginas e assets
├── infra/docker/               # PHP-FPM e Nginx
├── tests/frontend/             # Verificações estáticas/JS
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

5. Acesse **http://localhost:8082**. O Nginx serve o frontend atual e encaminha `/php/*` e `/api/*` para o Laravel.

Para desligar os serviços sem excluir dados:

~~~bash
docker compose --env-file backend/.env down
~~~

> **Atenção:** não execute `docker compose down -v` sem intenção explícita de apagar volumes, nem `migrate:fresh` no banco principal.

## Funcionalidades implementadas

- Registro, login, sessão e logout de usuários com autenticação Laravel e CSRF na interface de mesma origem.
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

Os workflows em `.github/workflows/` executam verificações de backend e frontend. Os scripts de concorrência em `backend/tests/Manual/` exigem **MySQL de testes isolado** (por exemplo, `gennus_v2_test`); ainda não são um gate de CI.

## Próximas prioridades

1. Concluir compatibilidade do campo `codigo_barras` na interface HTML/JS e fortalecer testes de contrato e autorização.
2. Reforçar a autenticação entre serviços, RBAC, documentação de API e consistência entre operações de vendas e estoque.
3. Automatizar os cenários concorrentes com MySQL/InnoDB e adicionar métricas de p95/p99.
4. Integrar o GenZIoT por um **worker Go** com limites de concorrência, backpressure, retentativas e rastreabilidade.
5. Migrar o frontend gradualmente para React + TypeScript; implementar serviços Python de análises/ML quando houver dados e casos de uso validados.

## Desenvolvimento

- Branch de trabalho: `develop/gennus-2`; `main` é atualizada após validação e merge.
- Preferir mudanças pequenas, testes antes de merge e commits descritivos.
- A interface de vendas/financeiro herdada do ERP original **não deve ser tomada como prova** de APIs transacionais completas.
- Para bugs ou melhorias, utilize as issues e o histórico de Pull Requests do repositório.

**Status:** projeto em evolução; não é uma distribuição pronta para produção.
