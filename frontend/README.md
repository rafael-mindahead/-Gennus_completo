# Frontend do Gennus 2.0 — reservado ao React

A interface legada HTML/CSS/JS foi retirada de `develop/gennus-2` para evitar retrabalho antes da reconstrução em **React + TypeScript**.

- **Design original e diretrizes de refatoração:** [`docs/DESIGN_SYSTEM.md`](../docs/DESIGN_SYSTEM.md)
- **Código legado recuperável (antes da limpeza):** [frontend no commit 3e904883](https://github.com/rafael-mindahead/-Gennus_completo/tree/3e90488311aa05a22e27c901e64f433c2964d2db/frontend)
- **Nginx:** permanece configurado, incluindo `/api/*`, `/php/*` e `/up` para testes do Laravel. A rota `/` fica sem interface até a chegada do React.

Não adicionar novas páginas HTML/JS legadas aqui. Criar a nova aplicação React quando o backend e os contratos estiverem prontos.
