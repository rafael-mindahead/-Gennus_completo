# Frontend Gennus 2.0 — CSS, JavaScript e sessão

## Dois modos de execução suportados

- **Live Server / VS Code (127.0.0.1:5500):** páginas abertas em `/frontend/...`. CSS, JavaScript e links entre HTMLs agora usam **caminhos relativos**, funcionando também sob esse prefixo. **É uma prévia visual; não executa o backend PHP/Laravel.** Assim, login, logout, sessão e operações CRUD ainda darão erro se tentadas pela porta 5500.
- **Nginx + Laravel (localhost:8082):** páginas em `/...`, como `http://localhost:8082/pages/finance/financas.html`. CSS e JS usam os mesmos caminhos relativos. Este é o endereço adequado para testar login, sessão, CSRF e os módulos já implementados no Laravel.

Exemplo de CSS em `frontend/pages/finance/financas.html`:
`../../assets/css/financas.css`.
No Live Server resolve `/frontend/assets/css/financas.css`; no Nginx resolve `/assets/css/financas.css`.

## Atualizar versão local e reiniciar ambiente

```sh
git status --short
# Se houver modificações locais, salve/commite ou guarde-as antes do pull
git pull origin develop/gennus-2
docker compose --env-file backend/.env up -d
docker compose --env-file backend/.env restart nginx
```

Para conferir as rotas estáticas:
```sh
curl -I http://localhost:8082/assets/css/financas.css
curl -I http://localhost:8082/assets/js/financas.js
curl -I http://127.0.0.1:5500/frontend/assets/css/financas.css
curl -I http://127.0.0.1:5500/frontend/assets/js/financas.js
```

Os quatro arquivos devem responder `200` se cada servidor estiver rodando. Na aba Network do navegador, confirme `Content-Type` CSS/JavaScript e código HTTP 200; uma resposta HTML 404 indica caminho ou servidor equivocado. Nginx de desenvolvimento usa `Cache-Control: no-store` para `/assets/`.

## Autenticação e logout

- Nas páginas internas, `data-logout` identifica o botão de saída.
- `frontend/assets/js/session.js` usa `Gennus.fetch` para chamar `POST /php/cliente_logoff.php` com token CSRF no mesmo host. Só redireciona após resposta de sucesso.
- Se `GET /php/valida_sessao.php` responder `401`, a sessão é inválida; o usuário precisa autenticar-se na porta `8082`.
- O frontend é HTML estático, então bloquear telas por JavaScript não substitui autenticação e autorização de cada endpoint do backend.

## Verificações automatizadas

```sh
python3 -m unittest discover -s tests/frontend -p 'test_*.py' -v
find frontend/assets/js -name '*.js' -print0 | xargs -0 -n1 node --check
node --test tests/frontend/session.test.cjs
```

O workflow GitHub Actions **Frontend static checks** valida links locais em dois prefixos (`/` e `/frontend/`), existência dos recursos CSS/JS, sintaxe e comportamento básico de sessão/logout.

## Funcionalidades futuras

Os módulos de produtos, clientes, funcionários, vendas e financeiro continuam chamando APIs `/php/*_get.php` do ERP antigo que ainda não foram reconstruídas no Laravel. Portanto, scripts podem **carregar corretamente** mas falhar ao buscar dados. Isso será tratado nas próximas etapas do backend; não substituir erros por indicadores fictícios.
