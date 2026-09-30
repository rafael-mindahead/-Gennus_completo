document.addEventListener("DOMContentLoaded", () => {
    const formVenda = document.getElementById("venda-form");
    const selectProd = document.getElementById("venda-produto");
    const listaVendasHtml = document.getElementById("lista-vendas-dia");

    let produtosDisponiveis = [];

    // Busca os produtos direto do banco (para garantir estoque real)
    async function carregarProdutos() {
        if (!selectProd) return;
        
        const retorno = await fetch('php/produto_get.php');
        const resposta = await retorno.json();

        selectProd.innerHTML = '<option value="">Selecione um produto</option>';
        
        if (resposta.status === 'ok') {
            produtosDisponiveis = resposta.data;
            produtosDisponiveis.forEach(p => {
                if (parseFloat(p.estoque) > 0) {
                    // Usamos o ID real do banco como value
                    selectProd.innerHTML += `<option value="${p.id}">${p.nome} (Disp: ${p.estoque} ${p.unidade})</option>`;
                }
            });
        }
    }

    // Busca as vendas do banco e mostra as 5 mais recentes
    async function renderVendas() {
        if (!listaVendasHtml) return;
        listaVendasHtml.innerHTML = "";

        const retorno = await fetch('php/venda_get.php');
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            // O PHP já pode trazer ordenado por DESC, então pegamos as 5 primeiras
            const recentes = resposta.data.slice(0, 5); 
            
            recentes.forEach(v => {
                // Formata a data vinda do MySQL (YYYY-MM-DD HH:MM:SS) para o formato local
                const dataFormatada = new Date(v.data_venda).toLocaleString('pt-BR');
                
                listaVendasHtml.innerHTML += `
                    <tr>
                        <td>${v.produto_nome}</td>
                        <td>${parseFloat(v.qtd).toFixed(2)} ${v.unidade}</td>
                        <td>R$ ${parseFloat(v.valor_total).toFixed(2)}</td>
                        <td>${dataFormatada}</td>
                    </tr>
                `;
            });
        }
    }

    // Registrar nova venda
    formVenda.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        const idProd = selectProd.value;
        const qtdVendida = parseFloat(document.getElementById("venda-qtd").value);
        
        if (!idProd) return alert("Selecione um produto!");

        // Encontra os dados completos do produto selecionado
        const produto = produtosDisponiveis.find(p => p.id == idProd);

        if (qtdVendida > parseFloat(produto.estoque)) {
            alert("Erro: Estoque insuficiente!");
            return;
        }

        // Calcula os totais no frontend (o backend também poderia fazer isso)
        const valorTotal = qtdVendida * parseFloat(produto.preco);
        const custoTotal = qtdVendida * parseFloat(produto.custo || 0);

        const fd = new FormData();
        fd.append("produto_id", produto.id);
        fd.append("produto_nome", produto.nome);
        fd.append("qtd", qtdVendida);
        fd.append("unidade", produto.unidade);
        fd.append("valor_total", valorTotal);
        fd.append("custo_total", custoTotal);

        const retorno = await fetch('php/venda_novo.php', { method: 'POST', body: fd });
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            alert("Venda realizada! O estoque foi atualizado automaticamente.");
            formVenda.reset();
            carregarProdutos(); // Recarrega os selects para atualizar o estoque na tela
            renderVendas();     // Atualiza a tabela
        } else {
            alert("Erro: " + resposta.mensagem);
        }
    });

    // Inicializa a tela
    carregarProdutos();
    renderVendas();
});