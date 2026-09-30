document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("produto-form");
    const listaHtml = document.getElementById("lista-produtos");

    // READ: Busca os produtos do banco via PHP
    async function renderizar() {
        if (!listaHtml) return;
        listaHtml.innerHTML = "";

        try {
            const retorno = await fetch('php/produto_get.php');
            const resposta = await retorno.json();

            if (resposta.status === 'ok') {
                resposta.data.forEach((p) => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${p.nome}</td>
                        <td>R$ ${parseFloat(p.custo).toFixed(2)}</td>
                        <td>R$ ${parseFloat(p.preco).toFixed(2)}</td>
                        <td>${parseFloat(p.estoque).toFixed(2)} ${p.unidade}</td>
                        <td>
                            <button onclick="editarProd(${p.id})" style="color:var(--roxo); background:none; border:none; cursor:pointer; font-weight:600;">Editar</button>
                            <button onclick="excluirProd(${p.id})" style="color:var(--vermelho); background:none; border:none; cursor:pointer; margin-left:10px; font-weight:600;">Excluir</button>
                        </td>
                    `;
                    listaHtml.appendChild(tr);
                });
                
                // Opcional: Atualiza o LocalStorage para a tela de vendas continuar lendo o estoque
                localStorage.setItem("gennus_prods", JSON.stringify(resposta.data));
            }
        } catch (error) {
            console.error("Erro ao carregar produtos:", error);
        }
    }

    // CREATE / UPDATE: Cadastrar novo ou alterar existente
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        const id = document.getElementById("prod-id").value;
        const fd = new FormData();
        
        fd.append("nome", document.getElementById("prod-nome").value);
        fd.append("categoria", document.getElementById("prod-categoria").value);
        fd.append("unidade", document.getElementById("prod-unidade").value);
        fd.append("custo", document.getElementById("prod-custo").value);
        fd.append("preco", document.getElementById("prod-preco").value);
        fd.append("estoque", document.getElementById("prod-estoque").value);

        let url = 'php/produto_novo.php';
        if (id !== "") {
            url = `php/produto_alterar.php?id=${id}`;
        }

        const retorno = await fetch(url, { method: 'POST', body: fd });
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            document.getElementById("prod-id").value = "";
            document.getElementById("btn-salvar-prod").textContent = "Salvar Produto";
            form.reset();
            renderizar();
            alert("Produto salvo com sucesso!");
        } else {
            alert("Erro: " + resposta.mensagem);
        }
    });

    // GET 1 REGISTRO: Puxar os dados para editar
    window.editarProd = async (id) => {
        const retorno = await fetch('php/produto_get.php?id=' + id);
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            const p = resposta.data[0];
            document.getElementById("prod-id").value = p.id;
            document.getElementById("prod-nome").value = p.nome;
            document.getElementById("prod-categoria").value = p.categoria || "";
            document.getElementById("prod-unidade").value = p.unidade || "un";
            document.getElementById("prod-custo").value = p.custo;
            document.getElementById("prod-preco").value = p.preco;
            document.getElementById("prod-estoque").value = p.estoque;
            
            document.getElementById("btn-salvar-prod").textContent = "Atualizar Produto";
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    // DELETE: Excluir o produto
    window.excluirProd = async (id) => {
        if(confirm("Excluir este produto?")) {
            const retorno = await fetch('php/produto_excluir.php?id=' + id);
            const resposta = await retorno.json();
            
            if(resposta.status === 'ok') {
                renderizar();
            } else {
                alert("Erro ao excluir: " + resposta.mensagem);
            }
        }
    };

    // Carrega a tabela assim que a página abrir
    renderizar();
});