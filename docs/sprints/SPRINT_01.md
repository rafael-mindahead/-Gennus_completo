# Sprint 1 — Início controlado da migração
Branch de trabalho: `develop/gennus-2`. Branch original congelada: `versao-1`.
Trello: https://trello.com/b/emek5h2a/gennus-20-sprints-e-backlog

## Etapa 1: baseline
- [x] Versionamento sem tocar na branch principal.
- [x] Pipeline de regressão **somente de leitura**, sem banco.
- [x] Identificação dos contratos existentes: GET `/php/{produto,cliente,venda}_get.php`, JSON `{status,mensagem,data}`.
- [ ] Executar o workflow GitHub Actions com resultado verde (depende de execução remota).
- [ ] Testar backup/restore em banco descartável.
- [ ] Rodar suíte HTTP de integração contra banco descartável (somente com `GENNUS_TEST_ALLOW_WRITES=1`).

## Contratos inegociáveis
1. A versão 1 continua acessível em `versao-1`; a `main` não é substituída.
2. Os GETs legados possuem campos e tipos JSON exigidos pelo iOS SwiftUI; nenhuma alteração de URL sem teste de contrato.
3. `app/sales.php` usa transação e `SELECT .. FOR UPDATE` para controlar estoque; manter ao migrar vendas.
4. `database/gennus_database.sql` tem `DROP TABLE`; **não executar no banco existente**.
5. O login HTTP e a futura API exigirão regras explícitas de autorização, incluindo acesso aos dados financeiros.
6. A nova aplicação Laravel terá banco de desenvolvimento separado do banco legado.
7. Não marcar tarefa Trello concluída antes de teste efetivo.

## Fases de commits (CRT)
1. `test/ci`: baseline e testes de regressão legados.
2. `feat`: estrutura paralela oficial Laravel 13 (PHP 8.3+), com rota health.
3. `chore`: Docker de desenvolvimento isolado + instruções de execução + CI Laravel.

## Próximo trabalho
Depois de validar ambiente isolado: migrar ações de produtos (`CreateProduto`, `GetProduto`, `UpdateProduto`, `DeleteProduto`), com validações, autenticação e testes. Nenhum endpoint legado foi alterado nesta etapa.
