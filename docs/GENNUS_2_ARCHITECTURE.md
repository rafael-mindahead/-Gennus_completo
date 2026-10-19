# Gennus 2.0 — Plano de migração

> Documento de arquitetura. A implementação ainda não foi executada. O legado permanece intacto na branch `main`.

## Backlog funcional
Consulte [GENNUS_2_FEATURE_BACKLOG.md](GENNUS_2_FEATURE_BACKLOG.md) para os módulos Cliente 360°, Produto 360°, presença de funcionários, GennIA, dependências de dados, critérios de aceite e priorização P0–P3.

## Objetivo e escopo
Migrar o ERP atual (HTML/CSS/JS, PHP procedural, MySQL, Docker, SwiftUI) incrementalmente para um **monólito modular Laravel** implantável na Azure, com CI/CD no GitHub Actions e análise de dados via OpenAI API. Preservar funcionalidades, dados, compatibilidade do iOS e **uma classe Action por operação CRUD**.

## Inventário confirmado no repositório
- CRUD de clientes, produtos, funcionários e despesas: `app/crud.php`, `public/php/*_novo.php`, `*_alterar.php`, `*_excluir.php`, `*_get.php`.
- Vendas/estoque: `app/sales.php` usa transações e `SELECT ... FOR UPDATE` para controlar concorrência, recalcula estoque e mantém preço histórico.
- Frontend HTML/JS: `public/pages`, `public/assets`.
- iOS SwiftUI: `mobile/ios/GennusIOS`; `APIService.swift` consome `/php/produto_get.php`, `cliente_get.php` e `venda_get.php`, no envelope JSON `{status,mensagem,data}`.
- Infra atual: Apache/PHP com Docker Compose, MySQL 8.0 e phpMyAdmin (somente desenvolvimento).
- Testes: `tests/test_api.py`, `tests/test_frontend.cjs`, `tests/test_paths.py`.
- Páginas financeiras demonstrativas ainda contêm dados simulados; não tratá-los como registros reais.

## Arquitetura-alvo
- Backend: Laravel API REST, autenticação e autorização explícitas, validação por Form Requests; monólito modular (não microserviços prematuros).
- `app/Actions/Produto/{CreateProduto,GetProduto,UpdateProduto,DeleteProduto}.php`; padrão análogo para os demais módulos.
- `app/Services/Sales/StockService.php`: operações de estoque em transações, bloqueio pessimista e preservação do histórico; testes de concorrência obrigatórios.
- `app/Services/AI/FinancialInsightsService.php`: consulta dados agregados validados e autorizados, envia somente contexto mínimo à OpenAI e devolve resumo explicável. IA jamais altera registros financeiros nem executa SQL arbitrário.
- Persistência MySQL usando migrations adaptadas ao esquema real; antes: dump seguro, inventário das colunas, testes de migração e rollback.
- API versionada `/api/v1` e **camada de compatibilidade** `/php/*.php` com métodos/respostas legados durante a transição; não redirecionar gravações POST de modo inseguro.
- Manter frontend existente inicialmente; modernização do frontend é etapa opcional posterior. Atualizar iOS de modo independente somente após testes de contrato.

## Infra e CI/CD alvo
- Desenvolvimento: Docker Compose com PHP/Laravel, Nginx, MySQL e opcional worker de fila; segredos em variáveis de ambiente locais não versionadas.
- Produção inicial: Azure App Service for Containers ou Azure Container Apps (decisão após custos/limites da assinatura); Azure Database for MySQL Flexible Server; Azure Container Registry se necessário.
- GitHub Actions: lint PHP, testes PHPUnit/Pest, testes de contrato e migrações em MySQL efêmero, análise estática, build e varredura da imagem. Deploy somente após checks aprovados para ambiente staging; produção com aprovação.
- Credenciais: GitHub OIDC + identidade federada Azure, Key Vault/segredos; jamais publicar `OPENAI_API_KEY` no web/mobile.
- Health checks, logs, retenção, backup/restore e rollback de imagem são requisitos de produção. Não executar `migrate:fresh` em banco existente.

## Fases sugeridas
1. **Baseline**: mapear endpoints, tabelas, telas e testes; verificar que os testes legados passam; obter backup restaurável.
2. **Laravel paralelo**: criar estrutura Laravel separada (por exemplo, `backend/`), Docker e health check, sem remover os arquivos legados.
3. **Contratos**: testar JSON `status/mensagem/data`, métodos, parâmetros, sessões, códigos HTTP e tipos numéricos; autenticação iOS precisa de análise específica.
4. **CRUD**: migrar módulo por módulo para Actions independentes; conservar validações e efeitos colaterais.
5. **Vendas**: portar e testar transações, concorrência, preço histórico e estoque.
6. **IA**: API proxy server-side, relatórios agregados por período, custos controlados, autorização por usuário, auditoria e não exposição de PII.
7. **Azure**: pipeline staging, deploy controlado, observabilidade, restore/rollback e posterior cutover.

## Exemplo de endpoint de insights (alvo)
`POST /api/v1/insights/financeiros` recebe `{periodo_inicio,periodo_fim,pergunta}`; o backend valida o acesso, calcula agregados de vendas, custos e despesas no MySQL, envia apenas resultados necessários à API da OpenAI e retorna `{status,mensagem,data:{resumo,indicadores,avisos}}`. Nunca aceitar instruções do modelo para modificar estoque, preços, usuários ou despesas.

## Restrições/decisões a validar
- Inventariar estrutura SQL completa antes de criar migrations.
- Definir política de login iOS: atual código Swift consulta endpoints GET; rota de compatibilidade precisa preservar comportamento enquanto se fecha acesso a dados de outras contas.
- Definir requisitos reais de uso/custo da Azure antes de contratar serviços.
- Planejar retenção, privacidade de dados, limites de tokens e respostas não determinísticas da IA.
- A migração só está pronta quando testes funcionais/contratos/concorrência passarem e os dados forem preservados.
