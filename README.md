# Gennus

ERP de estudo com interface HTML/CSS/JavaScript, API PHP, MySQL e aplicativo iOS em SwiftUI. A reorganização mantém as funcionalidades existentes e os endpoints `/php/*.php` consumidos pelo app.

## Organização

| Pasta | Responsabilidade |
| --- | --- |
| `public/index.html` | Página inicial |
| `public/pages/auth` | Login e cadastro |
| `public/pages/erp` | Dashboard, clientes, produtos, funcionários e vendas |
| `public/pages/finance` | Despesas, renda, relatórios e demonstrações visuais |
| `public/pages/account` | Perfil |
| `public/pages/legal` | Termos de uso |
| `public/assets/css`, `public/assets/js` | Estilos e comportamento das páginas |
| `public/php` | Endpoints HTTP, mantendo os nomes anteriores |
| `app` | Conexão, autenticação, validação, cadastros e regras de venda |
| `database` | Estrutura e dados de demonstração |
| `mobile/ios/GennusIOS` | Projeto Xcode e código Swift |
| `tests` | Verificações de caminhos, JavaScript e integração da API |
| `docs` | Notas sobre os contratos e funcionamento |

## Rodar com Docker

Requisitos: Docker com Compose.

```sh
cp .env.example .env
docker compose up --build -d
```

- Web: http://localhost:8080
- Login: http://localhost:8080/pages/auth/login.html
- API de produtos: http://localhost:8080/php/produto_get.php
- phpMyAdmin: http://localhost:8081
- Usuário local de demonstração: `admin@gennus.local`, senha `admin123`.

O Apache publica somente `public/`. O banco recebe o SQL automaticamente **apenas quando o volume está vazio**. O volume `gennus_mysql_data` foi mantido para preservar instalações existentes. `docker compose down` mantém os dados; `docker compose down -v` remove o volume e os dados.

O arquivo `database/gennus_database.sql` recria as tabelas e apaga seus dados. Use-o somente para iniciar um banco descartável; faça backup antes de qualquer importação manual em um banco existente. A refatoração não exige recriar tabelas de uma instalação que já tenha esse esquema.

`.env` não é versionado. As credenciais padrão são para desenvolvimento. Em execução direta, configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASSWORD` no ambiente do PHP. O PHP não carrega `.env` automaticamente fora do Compose.

```sh
php -S localhost:8080 -t public
```

Este comando pressupõe um banco inicializado e variáveis de ambiente configuradas. Redirecionamentos dos antigos nomes de página, como `/dashboard.html`, são definidos em `public/.htaccess` e exigem Apache com `mod_rewrite`; o servidor embutido usa diretamente `/pages/...`.

## Fluxos preservados e correções

Login, cadastro, perfil, clientes, produtos, funcionários, despesas e vendas continuam nas mesmas tecnologias. Não foram adicionados framework, serviço Python ou integração com AuthService.

- Novas senhas são armazenadas com `password_hash`; senhas antigas em texto são atualizadas após um login válido. A sessão não devolve a senha.
- Perfil salva no banco e atualiza a sessão, substituindo a simulação anterior em `localStorage`.
- Gravações e logout exigem POST; cadastros e vendas exigem sessão. IDs, valores, campos e benefícios são validados no servidor.
- Vendas calculam valores a partir dos preços do banco, bloqueiam o produto durante a transação e ajustam o estoque na criação, alteração e exclusão. O nome do produto na venda preserva o histórico após excluir o produto.
- Dashboard, despesas e renda usam custo dos itens vendidos, salários ativos e despesas manuais. Esses valores são totais dos registros; não representam fechamento contábil por competência.
- Gráficos diários usam as datas registradas e o calendário local. Projeções e o radar são indicativos, não previsões.
- `resumo.html` e `submenu-renda.html` continuam demonstrações com dados simulados, identificadas na tela. Opções sem implementação anterior, como IA Feedback, continuam sem funcionalidade.

## iOS

Abra `mobile/ios/GennusIOS/GennusIOS.xcodeproj` no Xcode. `APIService.swift` usa `http://localhost:8080/php` no simulador e trata falhas HTTP e respostas `nok`. A exceção HTTP em `Info.plist` está limitada a `localhost`. Para um aparelho físico, configure o endereço HTTPS do servidor: `localhost` aponta para o próprio aparelho.

As leituras de clientes, produtos e vendas preservam o contrato público já usado pelo app: JSON com `status`, `mensagem` e `data`, IDs numéricos, valores numéricos e listas vazias com `status: "ok"`. A proteção de sessão nas gravações não altera essas leituras. A API não depende dos arquivos da interface para responder às requisições.

## Verificação

```sh
python -m unittest discover -s tests -p test_paths.py -v
node --test tests/test_frontend.cjs
```

A verificação PHP pode ser feita com `php -l` nos arquivos de `app/` e `public/php/`.

Os testes de integração **criam e alteram registros e senhas**. Rode somente em um banco local descartável, inicializado com o SQL do projeto:

```sh
GENNUS_TEST_ALLOW_WRITES=1 GENNUS_TEST_BASE_URL=http://localhost:8080 \
  python -m unittest discover -s tests -p test_api.py -v
```

Os testes cobrem sessão/logout, cadastro e duplicidade, persistência do perfil, validações de CRUD, tipos do JSON, benefícios, transações, concorrência e histórico das vendas. A flag de escrita impede sua execução acidental sem autorização explícita no ambiente.

Nesta refatoração, PHP e JavaScript foram verificados e a API foi exercitada em banco descartável MariaDB 10.11 com PHP 8.3. Os formulários foram exercitados em DOM com a API real; Chart.js foi substituído nesse teste por um objeto que aceita sua configuração. Docker/MySQL 8 e compilação iOS exigem validação no ambiente de vocês; não foram executados aqui.

## Licença

[MIT](LICENSE).
