document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("gasto-form");
    const listaHtml = document.getElementById("lista-despesas-total");
    const totalGeralHtml = document.getElementById("total-geral-despesas");

    async function renderizar() {
        listaHtml.innerHTML = "";
        let somaTotal = 0;

        // 1. BUSCAR FUNCIONÁRIOS DO BANCO (Substitui o localStorage)
        try {
            const respFunc = await fetch('php/funcionario_get.php');
            const dataFunc = await respFunc.json();
            
            if (dataFunc.status === 'ok') {
                dataFunc.data.forEach(f => {
                    const valor = parseFloat(f.salario_base) || 0;
                    somaTotal += valor;
                    listaHtml.innerHTML += `
                        <tr>
                            <td>Salário: ${f.nome}</td>
                            <td style="color: #666">Automático</td>
                            <td class="txt-vermelho">R$ ${valor.toFixed(2)}</td>
                            <td><small>Não editável</small></td>
                        </tr>
                    `;
                });
            }
        } catch (e) { console.error("Erro ao carregar funcionários:", e); }

        // 2. BUSCAR GASTOS MANUAIS DO BANCO
        try {
            const retorno = await fetch('php/despesa_get.php');
            const resposta = await retorno.json();

            if (resposta.status === 'ok') {
                resposta.data.forEach((g) => {
                    somaTotal += parseFloat(g.valor);
                    // AQUI A CORREÇÃO: g.descricao aparece na primeira coluna
                    listaHtml.innerHTML += `
                        <tr>
                            <td>${g.descricao}</td>
                            <td style="color: var(--roxo)">Manual</td>
                            <td class="txt-vermelho">R$ ${parseFloat(g.valor).toFixed(2)}</td>
                            <td>
                                <button onclick="editarGasto(${g.id})" style="background:none; border:none; color:var(--roxo); cursor:pointer;">Editar</button>
                                <button onclick="excluirGasto(${g.id})" style="background:none; border:none; color:var(--vermelho); cursor:pointer; margin-left:10px;">Excluir</button>
                            </td>
                        </tr>
                    `;
                });
            }
        } catch (error) { console.error("Erro ao buscar despesas:", error); }

        totalGeralHtml.textContent = somaTotal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    // CREATE / UPDATE
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const id = document.getElementById("gasto-id").value;
        const fd = new FormData();
        fd.append("descricao", document.getElementById("gasto-desc").value);
        fd.append("valor", document.getElementById("gasto-valor").value);

        let url = 'php/despesa_novo.php'; 
        if (id !== "") url = `php/despesa_alterar.php?id=${id}`;

        const retorno = await fetch(url, { method: 'POST', body: fd });
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
        const retorno = await fetch('php/despesa_get.php?id=' + id);
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
            const retorno = await fetch('php/despesa_excluir.php?id=' + id);
            const resposta = await retorno.json();
            if(resposta.status === 'ok') renderizar();
            else alert("Erro: " + resposta.mensagem);
        }
    };

    renderizar();
});