# Organização e contratos existentes

A interface fica em `public/pages` e usa URLs absolutas para assets e endpoints. `public/php` contém entradas pequenas que delegam a `app`. O SQL e as fontes iOS ficam fora da raiz publicada pelo Apache.

| Contrato | Comportamento |
| --- | --- |
| `usuario_login.php`, `cliente_login.php` | POST; login principal e compatibilidade legada; hash e migração de senha antiga |
| `usuario_novo.php` | POST; cadastro principal |
| `usuario_alterar.php` | POST com sessão; persistência do formulário de perfil existente |
| `valida_sessao.php` | GET; objeto de usuário sem senha ou HTTP 401 |
| `cliente_logoff.php` | POST; encerra sessão e cookie |
| `*_get.php` | GET; `?id=` opcional; listas numéricas compatíveis com Swift; vazio retorna `ok` |
| `*_novo.php`, `*_alterar.php`, `*_excluir.php` | POST com sessão; alterar/excluir usam `?id=` |

As respostas mantêm `{status, mensagem, data}`. Validação retorna HTTP 400, ausência de sessão 401, registro inexistente 404, método incorreto 405 e conflito 409. Erros internos retornam mensagem genérica e detalhes ficam no log do servidor.

Vendas e estoque são gravados em uma transação com bloqueio de linha. Novas vendas ignoram preço, custo e nome enviados pelo navegador. Alterações de quantidade preservam a proporção dos valores históricos da venda, arredondando os totais para centavos; a tabela existente não armazena preço unitário histórico separado. Excluir uma venda devolve estoque se o produto ainda existir. Aumentar a quantidade de uma venda cujo produto foi excluído é bloqueado.

Os GETs públicos foram preservados por compatibilidade com o cliente iOS atual. A separação entre interface e API permite manter o acesso mobile mesmo se um arquivo de interface falhar; ambos continuam usando o mesmo servidor PHP neste projeto.

Esta etapa reorganiza e corrige o legado. Integração com AuthService, framework de frontend ou serviços Python fica para uma etapa posterior, pois adicionaria comportamento fora do escopo desta refatoração.
