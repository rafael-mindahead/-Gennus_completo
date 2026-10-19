# Gennus 2.0 — Backlog funcional e critérios de aceite
> Planejamento de produto; nada deste documento implica que as funcionalidades já foram implementadas. `main` permanece sem modificações. Referência de esquema: `database/gennus_database.sql`.

## Descobertas do sistema atual (bloqueios reais)
- `clientes` contém id, nome, email, telefone, documento e datas, mas **não há relacionamento cliente → vendas**.
- `vendas` representa uma linha de produto (produto_id, qtd, valor_total, custo_total, data_venda), não necessariamente uma compra/carrinho; portanto **ticket médio por pedido** ainda não é calculável fielmente.
- `produtos` tem preço/custo/estoque atuais; histórico de vendas tem snapshots de nome, total e custo, permitindo análise de margem bruta **por linha**.
- `funcionarios` possui cadastro, salário, tipo de contrato e status, mas não possui pontos, escalas nem ausências justificadas.
- `despesas` tem valor, descrição e criado_em; não há contas a pagar/receber, categorias ou conciliação.
- Parte de `resumo` e `submenu-renda` utiliza dados demonstrativos: não misturar com indicadores reais.

## Estratégia de implantação
Arquitetura: Laravel modular; continuar com Action separada para cada operação (`CreateX`, `GetX`, `UpdateX`, `DeleteX`) + Service de regras + controllers finos. Endpoints legados `/php/*.php` e envelope `{status,mensagem,data}` mantidos por adapters testados para proteger o iOS. Modelagem via migrations **incrementais, aditivas, reversíveis quando possível**; preservar transações/estoque e dados históricos. Antes de qualquer alteração no banco, fazer backup verificável. `gennus_database.sql` apaga tabelas: NUNCA executar em base existente.

## Fase P0 — pré-requisitos críticos
**G2-PBI-001 — Foundation Laravel + contrato legados.**
- Escopo: skeleton `backend/`, Docker/Nginx/MySQL, controllers/API versionada, autenticação, autorização, testes PHP e API, pipeline GitHub Actions staging.
- Aceite: CRUD legado e rotas GET iOS continuam a responder com campos/tipos JSON equivalentes; testes de concorrência em estoque.
**G2-PBI-002 — Relacionamento cliente/venda.**
- Adicionar `vendas.cliente_id` inicialmente nullable com FK/index ou uma modelagem de pedidos com FK após definir etapas; vendedor deve poder vincular cliente à venda.
- Aceite: vendas novas vinculadas a clientes; vendas históricas ficam **sem cliente** até reconciliação explícita auditada (não atribuir por similaridade).
- Expor `GET /api/v1/clientes/{id}/indicadores?inicio&fim`; autorização, filtros validados, paginação.
**G2-PBI-003 — Verdade dos KPIs.**
- Definir faturamento como soma de valor_total de vendas válidas; margem bruta = (receita - custo histórico) / receita; considerar devoluções/cancelamentos quando existir status.
- Não chamar de lucro líquido o valor sem tributos/despesas completas. Ticket médio por pedido somente após existir identificador de pedido real; até lá exibir ticket por linha de venda com rótulo explícito.
- Aceite: números conferidos com SQL e testes com datas de fronteira, soma decimal, nenhuma venda e categorias sem dados.

## Fase P1 — funcionalidades solicitadas
**G2-PBI-004 — Cliente 360°.**
- Tela de perfil com histórico de compras, faturamento por período, frequência, produto mais comprado, margem bruta de vendas atribuídas, dias desde última compra e comparação mensal.
- Ao criar novo cliente mostrar estado vazio claro, jamais inventar dados.
- Gráficos a partir do backend, com filtro de período e drill-down.
- Aceite: dados isolados por permissão/empresa; gráficos batem com registros; caso sem vendas exibe 'sem dados'.

**G2-PBI-005 — Produto 360°.**
- Receita, quantidade vendida, custo histórico, margem bruta, ranking, série temporal, estoque disponível, alerta de estoque mínimo configurável.
- Diferenciar rentabilidade de margem estimada por custos existentes; giro exige definição de estoque médio e janela confiável.
- Aceite: não multiplicar custo atual por vendas antigas; produto excluído continua no histórico como snapshot.

**G2-PBI-006 — Frequência e ponto do funcionário.**
- Modelos propostos: `escalas(id,funcionario_id,dia_semana,hora_inicio,hora_fim,intervalo,validade_inicio,validade_fim)`, `registros_ponto(id,funcionario_id,instante,tipo,origem,registrado_por)`, `ocorrencias_frequencia(id,funcionario_id,data,tipo,justificativa,status,aprovado_por)`, `ajustes_ponto(id,registro_id,valor_anterior,valor_novo,motivo,solicitado_por,aprovado_por)`.
- Entradas, saídas, intervalos, ausências justificadas, atrasos e horas trabalhadas **apenas com escala definida**, férias/afastamentos futuramente.
- Exigir autenticação, timezone explícito, correções aprovadas e auditoria; não permitir backdate silencioso; não usar IA para decisões automáticas de RH.
- Aceite: saída antes da entrada rejeitada; duplicidades controladas; ausências justificadas não contabilizadas como falta injustificada; edição mantém histórico.
- Alerta: módulo operacional não é automaticamente sistema de ponto válido para obrigações legais trabalhistas.

**G2-PBI-007 — GennIA insights por cliente e produto.**
- `POST /api/v1/gennia/perguntar` recebe `{escopo:'cliente'|'produto'|'geral',entidade_id?,inicio,fim,pergunta}`.
- Backend valida permissões e consulta **somente agregações pré-programadas**; não expor SQL arbitrário e não dar acesso direto da IA ao banco.
- Transmitir somente dados mínimos necessários, evitar dados pessoais, segredos, documentos e salários no prompt; credencial da API exclusivamente no servidor.
- Resposta: resumo, indicadores de origem, período, limitações e sugestões, diferenciando fato de hipótese; falha/timeout com erro legível e fallback.
- Aceite: perguntas sem dados respondem 'insuficiente'; cliente/empresa de outro usuário negado; IA não pode gravar estoque/vendas/RH; limite de custo por usuário e rate-limit. Mostrar que IA pode errar.

## Fase P2 — completar o ERP
**G2-PBI-008 — Pedidos e itens de pedido.** Modelar `pedidos(id,cliente_id,status,criado_em,...)` e `pedido_itens` para N produtos por compra; migração sem duplicar vendas; ticket médio por pedido e cancelamentos/devoluções auditados.
**G2-PBI-009 — Fornecedores e compras.** Cadastro, entradas e histórico de custo, compra vinculada a fornecedor; movimentação de estoque por recebimento transacional.
**G2-PBI-010 — Inventário e alertas.** `movimentos_estoque` imutáveis, mínimo/máximo e reposição, auditoria de ajustes, rastreio do motivo.
**G2-PBI-011 — Contas a pagar/receber.** Vencimentos, status de quitação, categorias, fluxo de caixa e projeção claramente indicada; sem prometer automação bancária.
**G2-PBI-012 — CRM e tarefas.** Tags, origem do lead, observações, próximo contato, lembretes e oportunidades; evitar dados pessoais desnecessários.
**G2-PBI-013 — Relatórios exportáveis.** Filtros e CSV/PDF, relatório por cliente/produto e transparência de período; travar fórmulas contra CSV injection.
**G2-PBI-014 — Perfis e auditoria.** Administrador/gestor/vendas/financeiro/RH, políticas por recurso, log de operações sensíveis; isolamento multiempresa somente se requerido.

## Fase P3 — diferenciais (depois de dados confiáveis)
**G2-PBI-015 — GennIA proativa.** Alertas de anomalias de receita/margem, clientes inativos e estoque, com evidência, limiares configuráveis e aprovação humana.
**G2-PBI-016 — Previsões.** Demanda/reposição e sazonalidade com métricas de erro e dados suficientes; modelo estatístico separado de narrativa LLM.
**G2-PBI-017 — Portal e notificações.** Portal interno do colaborador e notificações de lembretes; controle de acesso e consentimentos aplicáveis.
**G2-PBI-018 — Integrações fiscais e de pagamento.** Analisar requisitos tributários, município/estado, provedor e certificação antes de escopo; não apresentar como conformidade automática.

## Dependências de dados
```
clientes ─── (cliente_id) ── vendas ── (produto_id) ── produtos
                                 |
                       futuro: pedidos/pedido_itens
funcionarios ── registros_ponto / escalas / ocorrencias / ajustes_ponto
produtos ────── movimentos_estoque / compras ── fornecedores
usuarios ────── permissoes / auditoria / consultas_gennia
```
O vínculo de cliente não existe ainda e deve ser criado antes de prometer indicadores individuais reais. Preservar snapshots financeiros e concorrência do `app/sales.php`. Se houver múltiplas empresas, adicionar `tenant_id` consistentemente com autorização.

## Endpoints-alvo exemplificativos (NÃO implementados)
- `GET /api/v1/clientes/{id}/indicadores?inicio=YYYY-MM-DD&fim=YYYY-MM-DD`
- `GET /api/v1/produtos/{id}/indicadores?inicio=YYYY-MM-DD&fim=YYYY-MM-DD`
- `GET /api/v1/funcionarios/{id}/frequencia?mes=YYYY-MM`
- `POST /api/v1/funcionarios/{id}/ponto` (registro imutável)
- `POST /api/v1/gennia/perguntar` (somente leitura)

## Entrega incremental/CRT sugerida
1. Congelar testes de contrato e base de dados (G2-PBI-001).
2. Migrar `produtos` + Product 360 (PBI-005): métricas já suportadas no esquema, rápido valor visível.
3. Migrar `clientes`, vincular vendas a clientes, Client 360 (PBI-002/004).
4. Estruturar `funcionarios` e lançar presença + auditoria (PBI-006).
5. Implantar GennIA somente após fonte confiável de indicadores (PBI-007).
6. Complementar pedidos, fornecedores, inventário e financeiro (P2) conforme demanda.
7. Azure/CI-CD: pipeline antes de qualquer produção; monitorar custos de hospedagem, MySQL e tokens separadamente.

## Requisitos transversais de aceite
- Preservação de dados, versionamento e migração testada em cópia do banco.
- Acessibilidade mobile/responsiva, estados sem dados, filtros por período, paginação.
- Respostas com indicadores rastreáveis e definições claras (receita ≠ lucro líquido).
- Segregação de dados financeiros e de RH por política, logs sem segredos, LGPD/retencão alinhados com uso real.
- Suíte automatizada para CRUD, autorização, consultas agregadas, transações e contratos SwiftUI.


## G2-PBI-019 — Comprovante de venda demonstrativo (SEM VALOR FISCAL)
**Objetivo:** após registrar uma venda/pedido, permitir visualizar, imprimir, baixar em PDF e reimprimir um comprovante comercial **simulado** para demonstração acadêmica, sem qualquer valor fiscal.

**Campos exibidos:** marca e dados de empresa fictícia/identificada como demonstração; número sequencial **interno** `DEMO-000001`, data/hora local, cliente opcional, vendedor opcional, descrição de itens, quantidade, preço unitário, subtotal, descontos, total, forma de pagamento (informativa) e status de cancelamento. O número interno nunca será denominado série/numero de nota fiscal.

**Avisos obrigatórios:** exibir em fonte legível e em destaque no topo e no rodapé `DOCUMENTO DEMONSTRATIVO — SEM VALOR FISCAL` e `NÃO É NF-e, NFC-e OU NFS-e`. Nunca incluir logo da SEFAZ, chave de acesso de 44 dígitos, QR Code de consulta fiscal, XML fiscal ou layout que se passe por autorização tributária. Não atribuir CNPJ real sem permissão; dados de demonstração devem ser claramente fictícios.

**Arquitetura proposta:** `GerarComprovanteDemo.php` (Action) e `ComprovanteDemoService.php` compõem renderização HTML para impressão/PDF a partir de venda/pedido persistido e autorizado. Usar os valores históricos de cada item, arredondamento decimal estável, tributos não calculados/sem discriminação inventada, e informações de pagamento apenas se registradas. Não inferir que pagamento foi efetivado. `GET /api/v1/vendas/{id}/comprovante-demo` ou `GET /api/v1/pedidos/{id}/comprovante-demo` com acesso autenticado; ações de download e impressão no frontend.

**Critérios de aceite:**
- Comprovante tem marcações explícitas de simulação e não contém elementos de documento fiscal oficial.
- Valores fecham com venda/pedido, múltiplos itens e desconto registrado, sem modificar estoque nem banco durante a impressão.
- Reimpressão repete valores históricos e exibe status atual (inclusive cancelamento).
- Cliente ausente e vendas legadas têm apresentação adequada; nenhum dado de pessoa de outra conta é exposto.
- Testes para autorização, PDF/HTML, formatação, números, desconto, devolução/cancelamento, UTF-8 e regressão iOS.

**Dependências:** G2-PBI-001, G2-PBI-008 e, para múltiplos itens, G2-PBI-012. Recurso agendado na **Sprint 2**, com refinamentos de exportação na Sprint 5.

## Organização no Trello — cinco sprints
Quadro privado: https://trello.com/b/emek5h2a/gennus-20-sprints-e-backlog
- Sprint 1: base Laravel + Docker + CI inicial + Produto 360° — G2-001 a G2-007.
- Sprint 2: Cliente 360°, relação vendas, pedidos e comprovante demonstrativo — G2-008 a G2-015.
- Sprint 3: RH, escalas, registros de presença e relatório — G2-016 a G2-021.
- Sprint 4: GennIA, análises, segurança e custo — G2-022 a G2-027.
- Sprint 5: fornecedores, estoque, financeiro, CRM, relatórios, Azure e entrega — G2-028 a G2-035.

Os 35 cards representam o plano de trabalho, não funcionalidades já entregues. Prazos e responsáveis ainda não foram definidos.
