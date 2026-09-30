document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("gasto-form");
    const listaHtml = document.getElementById("lista-despesas-total");
    const totalGeralHtml = document.getElementById("total-geral-despesas");

    async function renderizar() {
        listaHtml.innerHTML = "";
        let somaTotal = 0;

        const snapshot = await Gennus.finance.load();
        const salaries = snapshot.funcionarios.filter(func => func.status === 'Ativo');
        salaries.forEach(func => {
            listaHtml.innerHTML += `<tr><td>Salário: ${Gennus.escape(func.nome)}</td><td>Automático</td><td class="txt-vermelho">R$ ${Number(func.salario_base).toFixed(2)}</td><td><small>Não editável</small></td></tr>`;
        });
        listaHtml.innerHTML += `<tr><td>Custo dos produtos vendidos</td><td>Automático</td><td class="txt-vermelho">R$ ${snapshot.totals.estoque.toFixed(2)}</td><td><small>Não editável</small></td></tr>`;
        snapshot.despesas.forEach(g => {
            listaHtml.innerHTML += `<tr><td>${Gennus.escape(g.descricao)}</td><td>Manual</td><td class="txt-vermelho">R$ ${Number(g.valor).toFixed(2)}</td><td><button onclick="editarGasto(${g.id})">Editar</button> <button onclick="excluirGasto(${g.id})">Excluir</button></td></tr>`;
        });
        somaTotal = snapshot.totals.despesas;

        totalGeralHtml.textContent = somaTotal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    // CREATE / UPDATE
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const id = document.getElementById("gasto-id").value;
        const fd = new FormData();
        fd.append("descricao", document.getElementById("gasto-desc").value);
        fd.append("valor", document.getElementById("gasto-valor").value);

        let url = '/php/despesa_novo.php'; 
        if (id !== "") url = `/php/despesa_alterar.php?id=${id}`;

        const retorno = await Gennus.fetch(url, { method: 'POST', body: fd });
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            document.getElementById("gasto-id").value = "";
            document.getElementById("btn-salvar-gasto").textContent = "Salvar Gasto";
            form.reset();
            renderizar();
        } else {
            alert("Erro: " + resposta.mensagem);
        }
    });

    window.editarGasto = async (id) => {
        const retorno = await Gennus.fetch('/php/despesa_get.php?id=' + id);
        const resposta = await retorno.json();
        if (resposta.status === 'ok') {
            const g = resposta.data[0];
            document.getElementById("gasto-id").value = g.id;
            document.getElementById("gasto-desc").value = g.descricao;
            document.getElementById("gasto-valor").value = g.valor;
            document.getElementById("btn-salvar-gasto").textContent = "Atualizar Gasto";
        }
    };

    window.excluirGasto = async (id) => {
        if (confirm("Remover esta despesa?")) {
            const retorno = await Gennus.fetch('/php/despesa_excluir.php?id=' + id, {method: 'POST'});
            const resposta = await retorno.json();
            if(resposta.status === 'ok') renderizar();
            else alert("Erro: " + resposta.mensagem);
        }
    };

    renderizar();
});