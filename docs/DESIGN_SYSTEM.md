# Gennus 2.0 — Design System e memória visual

> **Referência histórica, não implementação pronta.** Este documento conserva a identidade e as ideias de UX da interface HTML/CSS/JS retirada da branch `develop/gennus-2` em 23/11/2026. É o ponto de partida para um **novo frontend React + TypeScript**, não uma obrigação de copiar o CSS antigo.

## 1. Direção de arte

- **Personalidade:** ERP financeiro/operacional com aparência premium, enxuta, moderna e orientada a dados.
- **Clima visual:** dark mode quase preto, superfícies discretamente elevadas, roxo vibrante como acento. O contraste remete à linguagem de fintechs como Nubank, mas a marca e a composição são próprias do **Gennus**.
- **Prioridade:** números de negócio, estados operacionais e decisões, não efeitos cosméticos.
- **O que queremos preservar:** sidebar sólida, identidade tipográfica, cards de KPI, charts objetivos, estados por cor e hierarquia clara.
- **O que não preservar literalmente:** HTML repetido, CSS por página, dependência de IDs globais, dados fictícios nem layouts pouco responsivos.

## 2. Paleta observada no código legado

Os valores abaixo foram extraídos de `frontend/assets/css/dashboard.css`, `financas.css`, `produtos.css` e `charts.css` antes da limpeza. São uma **referência documentada**; na recriação React, revisar contraste e nomes semânticos.

| Token sugerido | Valor legado | Papel |
| --- | --- | --- |
| `--color-bg` | `#000000` | Fundo principal |
| `--color-sidebar` | `#080808` | Navegação lateral |
| `--color-surface` | `#0C0C0C` | Cards e painéis |
| `--color-primary` | `#A855F7` | Roxo de marca, ação primária, destaque |
| `--color-primary-hover` | `#9333EA` | Interação/hover |
| `--color-primary-dark` | `#7E22CE` | Gradientes, gráfico de rosca |
| `--color-text` | `#FFFFFF` | Texto principal |
| `--color-text-muted-legacy` | `#666666` | Texto secundário **(precisa de contraste melhor)** |
| `--color-success` | `#22C55E` | Receita/resultado positivo |
| `--color-danger` | `#F87171` | Despesa/alerta/estoque baixo |
| `--color-border` | `rgba(255,255,255,0.05)` | Separadores discretos |

Em `charts.css` existiam ainda as cores de séries `#C084FC` (roxo claro), `#60A5FA` (azul), `#FB923C` (laranja) e `#FBBF24` (amarelo). O logo já usava gradiente roxo `#7E22CE → #A855F7` em algumas páginas.

### Regras de uso

- Roxo identifica a ação principal e a seleção ativa; não tornar todas as informações roxas.
- Verde e vermelho são sinais **semânticos**, mas devem ter texto/ícones associados: nunca cor como única comunicação.
- Cards: fundo `#0C0C0C`, borda leve, raio predominante **12–16 px**, espaçamento de **20–25 px**.
- Inputs escuros e botão roxo; na reconstrução, acrescentar estados de foco acessíveis e validação por campo.
- Escala de cinzas do legado necessita ser recalibrada: `#666666` em preto pode ter legibilidade insuficiente.

## 3. Tipografia e linguagem

- **Space Grotesk**: títulos, marca, KPI e números de destaque; grafismo mais característico.
- **Inter**: corpo, tabelas, formulários e navegação; leitura técnica.
- Títulos fortes, descrições curtas, labels discretos, valores em `pt-BR` e moeda `BRL`.
- Preservar tom objetivo e rótulos precisos: "Receita Bruta", "Despesas Totais", "Lucro Líquido", "Produtos", "Relatórios".
- Evitar excesso de caixa alta e textos muito pequenos no novo React: isso existia no legado, mas não é uma decisão definitiva.

## 4. Layout e componentes reutilizáveis

### Estrutura visual

- Sidebar escura fixa à esquerda com marca Gennus, navegação por domínio e bloco de perfil no rodapé.
- Sidebar do legado variava de **280 a 300 px** conforme a página; novo layout deverá ter uma largura consistente.
- Conteúdo com título, contexto, filtros, cards de KPI, gráficos, tabelas e formulários.
- Grid de KPI com três colunas no dashboard, transformado em 2/1 no tablet/mobile.
- Cards de gráfico em duas colunas (e card de largura dupla para detalhes), colapsando para uma abaixo de ~900 px.
- Hierarquia espacial simples, bordas suaves e animações de entrada discretas; respeitar preferência por movimento reduzido.

### Biblioteca de componentes a projetar no React

`AppShell`, `Sidebar`, `Topbar`, `PageHeader`, `KpiCard`, `ChartCard`, `PeriodFilter`, `DataTable`, `StatusBadge`, `FormField`, `EmptyState`, `ErrorState`, `LoadingSkeleton`.

O novo frontend precisa distinguir **sem dados**, **carregando**, **erro da API**, **sem permissão** e **dados disponíveis**. Não substituir ausência de endpoint por mock silencioso.

## 5. Ideias e semântica dos gráficos

O frontend antigo usava **Chart.js**. Isso é uma referência de visualização, não uma exigência de biblioteca para React.

| Visual | Ideia original | Cuidados na recriação |
| --- | --- | --- |
| **Linha/área** | Vendas e fluxo por dia, linha roxa com preenchimento translúcido | Respeitar intervalo, fuso, datas faltantes e unidade monetária |
| **Rosca (doughnut)** | Composição de saídas/custos: estoque, equipe/salários e gastos manuais | Informar valores e percentuais, legenda legível, evitar dados inventados |
| **Barras** | Vendas por categoria | Categorias consistentes, ordenação, rótulos e comparação em R$ |
| **Radar** | Visão exploratória de "saúde operacional": vendas, equipe, estoque, margem, gastos | Só usar se indicadores tiverem escalas comparáveis e metodologia documentada |
| **Cards de KPI** | Receita Bruta, Despesas Totais, Lucro Líquido | Definições de negócio centralizadas no Laravel; evidenciar período e atualização |

### Filtros e microinterações

O estilo de gráficos previa um seletor de período **7d / 30d / 3m / 1a** em `charts.css`, com opção ativa roxa e transição suave ao atualizar o canvas. **Isso aparece como conceito de CSS, não como garantia de funcionalidade da antiga `charts.html`.** Na versão React, o filtro deve consultar/agregar dados reais e persistir uma seleção coerente durante a navegação.

O design antigo previa: gráficos com contornos suaves, legenda com marcadores coloridos, hover em cards (`translateY(-2px)`), indicadores de tendências e tratamento de telas estreitas.

### Alertas de qualidade dos dados

O código legado incluía uma simulação de **"lucro +20%"**, um texto estático **"crescimento 12,5%"** e indicador de **"risco de estoque: Baixo"**. Eram demonstrações/conceitos visuais, **não indicadores validados pelo backend**. Não transportar esses números como verdade na SPA.

O radar legado calculava pontuações com limites ilustrativos para vendas, equipe e estoque. A futura métrica operacional necessita uma fórmula auditável, não escalas arbitrárias.

## 6. Princípios para a reconstrução React

1. **Design tokens semânticos** em um único lugar: cores, tipografia, raios, espaçamentos e estados.
2. **Componentes reais e acessíveis**, com navegação por teclado, foco visível, contraste adequado, responsividade e suporte a leitores de tela.
3. **Separação de responsabilidades:** Laravel fornece dados, autorização e métricas; React renderiza visualizações sem duplicar regras de negócio.
4. **Contratos HTTP versionados e tipados** (avaliar OpenAPI + clientes TypeScript), com estados de erro por endpoint.
5. **Visualização honesta:** toda série deve ter fonte, período, unidade, última atualização e tratamento de ausência de dados.
6. **Melhorar, não clonar:** preservar o DNA roxo/preto e a linguagem de gráficos, mas reformular UX, componentes e organização.

## 7. Onde recuperar a implementação antiga

O último commit conhecido antes desta limpeza é **`3e90488311aa05a22e27c901e64f433c2964d2db`**:

- [Arquivos históricos do frontend](https://github.com/rafael-mindahead/-Gennus_completo/tree/3e90488311aa05a22e27c901e64f433c2964d2db/frontend)
- [CSS do dashboard](https://github.com/rafael-mindahead/-Gennus_completo/blob/3e90488311aa05a22e27c901e64f433c2964d2db/frontend/assets/css/dashboard.css)
- [CSS dos charts](https://github.com/rafael-mindahead/-Gennus_completo/blob/3e90488311aa05a22e27c901e64f433c2964d2db/frontend/assets/css/charts.css)
- [Implementação dos charts](https://github.com/rafael-mindahead/-Gennus_completo/blob/3e90488311aa05a22e27c901e64f433c2964d2db/frontend/assets/js/charts.js)
- [Dashboard original](https://github.com/rafael-mindahead/-Gennus_completo/blob/3e90488311aa05a22e27c901e64f433c2964d2db/frontend/pages/erp/dashboard.html)
- [Página de Business Intelligence](https://github.com/rafael-mindahead/-Gennus_completo/blob/3e90488311aa05a22e27c901e64f433c2964d2db/frontend/pages/finance/charts.html)

Esses links preservam a fonte de referência no Git, mesmo após a exclusão dos arquivos na branch ativa.

## 8. Checkpoint de design para o futuro

Antes do primeiro PR de React, revisar este documento; definir tokens finais com contraste aferido; prototipar AppShell, Dashboard e Página de Produtos; validar com dados e endpoints reais; e automatizar testes de UI. O legado **não será mantido em paralelo** apenas para reproduzir telas.

**Decisão de arquitetura:** manter Nginx e suas rotas existentes durante a fase backend-only. A pasta `frontend/` guarda apenas um README provisório; `/` não possui página ativa até a implementação da nova SPA.
