// Javascript/funcionarios.js
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("func-form");
    const listaHtml = document.getElementById("lista-func");
    const tipoContrato = document.getElementById("tipo-contrato");
    const labelDoc = document.getElementById("label-doc");
    const documentoInput = document.getElementById("documento");
    const btnAddBenefit = document.getElementById("add-benefit");
    const extraBenefitsContainer = document.getElementById("extra-benefits-container");

    // Alternar entre CPF e CNPJ
    tipoContrato.addEventListener("change", () => {
        if (tipoContrato.value === "PJ") {
            labelDoc.textContent = "CNPJ";
            documentoInput.placeholder = "00.000.000/0001-00";
        } else {
            labelDoc.textContent = "CPF";
            documentoInput.placeholder = "000.000.000-00";
        }
    });

    function criarLinhaBeneficio(valor = "") {
        const div = document.createElement("div");
        div.style.display = "flex";
        div.style.gap = "10px";
        div.style.marginTop = "10px";
        div.style.alignItems = "center";
        
        div.innerHTML = `
            <div style="flex: 1;">
                <input type="text" class="extra-benefit" value="${valor}" placeholder="Ex: Gympass: R$ 50,00" style="width: 100%; background: #000; border: 1px solid rgba(255,255,255,0.05); padding: 12px; border-radius: 8px; color: #fff;">
            </div>
            <button type="button" class="btn-remove" style="background: rgba(248,113,113,0.1); color: #f87171; border: 1px solid rgba(248,113,113,0.2); padding: 10px; border-radius: 8px; cursor: pointer;">✕</button>
        `;

        div.querySelector(".btn-remove").addEventListener("click", () => div.remove());
        extraBenefitsContainer.appendChild(div);
    }

    btnAddBenefit.addEventListener("click", () => criarLinhaBeneficio());

    // READ: Busca funcionários do PHP
    async function renderizar() {
        if (!listaHtml) return;
        listaHtml.innerHTML = "";

        const retorno = await fetch('php/funcionario_get.php');
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            resposta.data.forEach((f) => {
                const tr = document.createElement("tr");
                // CORREÇÃO: Adicionada a coluna de salário base abaixo
                tr.innerHTML = `
                    <td>${f.nome}</td>
                    <td>${f.tipo_contrato}</td>
                    <td>${f.documento}</td>
                    <td>R$ ${parseFloat(f.salario_base).toFixed(2)}</td> 
                    <td>${f.status}</td>
                    <td>
                        <button onclick="editar(${f.id})" style="background:none; border:none; color:var(--roxo); cursor:pointer; font-weight:600;">Editar</button>
                        <button onclick="excluir(${f.id})" style="background:none; border:none; color:var(--vermelho); cursor:pointer; font-weight:600; margin-left:10px;">Excluir</button>
                    </td>
                `;
                listaHtml.appendChild(tr);
            });

            // Atualiza o LocalStorage para o painel de finanças calcular o total
            localStorage.setItem("gennus_funcs", JSON.stringify(resposta.data));
        }
    }

    // CREATE / UPDATE
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const id = document.getElementById("func-id").value;
        const extras = Array.from(document.querySelectorAll(".extra-benefit")).map(input => input.value);
        
        const fd = new FormData();
        fd.append("nome", document.getElementById("nome").value);
        fd.append("tipo", tipoContrato.value);
        fd.append("doc", documentoInput.value);
        fd.append("salario", document.getElementById("salario").value);
        fd.append("status", document.getElementById("status").value);
        fd.append("vt", document.getElementById("vt").value);
        fd.append("vr", document.getElementById("vr").value);
        fd.append("extras", JSON.stringify(extras));

        let url = 'php/funcionario_novo.php';
        if (id !== "") {
            url = `php/funcionario_alterar.php?id=${id}`;
        }

        const retorno = await fetch(url, { method: 'POST', body: fd });
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            document.getElementById("func-id").value = "";
            document.getElementById("btn-salvar").textContent = "Salvar Funcionário";
            extraBenefitsContainer.innerHTML = "";
            form.reset();
            renderizar();
            alert("Dados salvos com sucesso!");
        } else {
            alert("Erro: " + resposta.mensagem);
        }
    });

    // GET 1 Registro para Edição
    window.editar = async (id) => {
        const retorno = await fetch('php/funcionario_get.php?id=' + id);
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            const f = resposta.data[0];
            document.getElementById("func-id").value = f.id;
            document.getElementById("nome").value = f.nome;
            document.getElementById("tipo-contrato").value = f.tipo_contrato;
            document.getElementById("documento").value = f.documento;
            document.getElementById("salario").value = f.salario_base;
            document.getElementById("status").value = f.status;
            document.getElementById("vt").value = f.vt || "";
            document.getElementById("vr").value = f.vr || "";

            extraBenefitsContainer.innerHTML = "";
            if (f.extras) {
                const extrasArray = JSON.parse(f.extras);
                extrasArray.forEach(beneficio => criarLinhaBeneficio(beneficio));
            }

            document.getElementById("btn-salvar").textContent = "Atualizar Cadastro";
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    // DELETE
    window.excluir = async (id) => {
        if(confirm("Deseja realmente excluir este funcionário?")) {
            const retorno = await fetch('php/funcionario_excluir.php?id=' + id);
            const resposta = await retorno.json();
            
            if(resposta.status === 'ok') {
                renderizar();
            } else {
                alert("Erro ao excluir: " + resposta.mensagem);
            }
        }
    };

    renderizar();
});