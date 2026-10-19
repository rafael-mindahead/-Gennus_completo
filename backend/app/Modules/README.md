# Módulos de negócio
Planejado: `Produtos/`, `Clientes/`, `Vendas/`, `Funcionarios/`, `Presenca/`, `Financeiro/`, `GennIA/`.
Cada CRUD possuirá sua própria Action: `CreateProduto.php`, `GetProduto.php`, `UpdateProduto.php`, `DeleteProduto.php`.
Services centralizam regras complexas, Requests validam dados, Policies autorizam acesso.
Nenhum CRUD foi migrado nesta etapa: preservar o legado até os testes de compatibilidade.
