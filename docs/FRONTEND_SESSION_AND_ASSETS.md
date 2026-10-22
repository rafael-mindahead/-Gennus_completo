# Frontend do Gennus 2.0 — validação de recursos e logout

Este documento registra apenas o escopo do frontend. O backend ERP continua em migração.

## Ambiente local
- Frontend estático servido pelo Nginx em **http://localhost:8082**.
- CSS e JavaScript devem ser acessados por caminhos absolutos, como `/assets/css/main.css` e `/assets/js/session.js`.
- Não usar Live Server do VS Code (porta 5500) para testar a autenticação: ele não repassa os endpoints `/php/` ao Laravel.
- Nginx de desenvolvimento retorna `Cache-Control: no-store` em `/assets/`, para refletir alterações sem conteúdo antigo em cache.

## Logout
- Os links de saída internos utilizam `data-logout` (ERP, finanças, perfil e dashboard).
- `frontend/assets/js/session.js` intercepta apenas esses links, chama `POST /php/cliente_logoff.php` via `Gennus.fetch` (token CSRF do `common.js`) e somente depois do sucesso abre o login.
- Se o servidor recusar o logout, exibe erro e mantém a navegação atual.
- Resposta `401` de `GET /php/valida_sessao.php` redireciona ao login. Dados do usuário são aplicados com `textContent` para não injetar HTML.
- **Importante**: HTML estático continua acessível via URL; controles visuais não substituem autenticação e autorização das APIs.

## Verificar no Mac (sem apagar dados do MySQL)
```sh
git status --short
# Se houver mudanças locais, finalize/guarde-as antes de atualizar a branch
git pull origin develop/gennus-2
docker compose --env-file backend/.env up -d
docker compose --env-file backend/.env restart nginx

curl -I http://localhost:8082/assets/css/main.css
curl -I http://localhost:8082/assets/js/session.js
curl -i http://localhost:8082/php/valida_sessao.php
```

Os dois arquivos estáticos devem responder 200 com tipos CSS/JS, respectivamente. Sem login, a sessão deve responder 401; com login, 200 e JSON `status=ok`.

No navegador: abrir DevTools → Network e verificar que `/assets/css/*.css` e `/assets/js/*.js` respondem 200. Depois efetuar login e clicar em **Sair**, confirmando `POST /php/cliente_logoff.php` com 200 e um novo `GET /php/valida_sessao.php` com 401. Uma falha 419 aponta para sessão/token CSRF.

## Testes automatizados (GitHub Actions)
```sh
python3 -m unittest discover -s tests/frontend -p 'test_*.py' -v
find frontend/assets/js -type f -name '*.js' -print0 | xargs -0 -n1 node --check
node --test tests/frontend/session.test.cjs
```

O workflow `frontend-checks.yml` executa esses testes nas alterações do frontend. Ele não substitui o smoke test de navegador.

## Limitação intencional
As páginas de produtos, clientes, funcionários, vendas, despesas e alguns gráficos ainda usam `/php/*_get.php` e endpoints CRUD do backend antigo. Essas rotas **ainda não foram reconstruídas no Laravel**; receber 404 ou erro de API nelas não significa que o CSS/JS não carregou. Não preencher KPIs com zeros inventados ou dados falsos para mascarar erros. Isso será implementado nas próximas etapas do backend.
