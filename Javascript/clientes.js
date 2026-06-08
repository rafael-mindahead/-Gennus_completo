document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("cliente-form");
    const listaHtml = document.getElementById("lista-clientes");
    const btnSalvar = document.getElementById("btn-salvar");

    // READ: Busca clientes do banco e renderiza
    async function renderizarClientes() {
        const retorno = await fetch('php/cliente_get.php');
        const resposta = await retorno.json();

        listaHtml.innerHTML = "";

        if (resposta.status === 'ok') {
            resposta.data.forEach((cli) => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${cli.nome}</td>
                    <td>${cli.email}</td>
                    <td>${cli.telefone}</td>
                    <td>${cli.documento}</td>
                    <td>
                        <button class="btn-edit" onclick="editarCliente(${cli.id})">Editar</button>
                        <button class="btn-delete" onclick="excluirCliente(${cli.id})">Excluir</button>
                    </td>
                `;
                listaHtml.appendChild(tr);
            });
        }
    }

    // CREATE / UPDATE: Salvar ou atualizar cliente
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        const id = document.getElementById("cliente-id").value;
        const fd = new FormData();
        
        fd.append("nome", document.getElementById("nome").value);
        fd.append("email", document.getElementById("email").value);
        fd.append("telefone", document.getElementById("telefone").value);
        fd.append("documento", document.getElementById("documento").value);

        let url = 'php/cliente_novo.php'; // Padrão: criar novo
        
        // Se tem ID, muda a URL para a de alteração (passando ID na URL)
        if (id !== "") {
            url = `php/cliente_alterar.php?id=${id}`;
        }

        const retorno = await fetch(url, { method: 'POST', body: fd });
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            document.getElementById("cliente-id").value = "";
            btnSalvar.textContent = "Cadastrar Cliente";
            form.reset();
            renderizarClientes();
        } else {
            alert("Erro: " + resposta.mensagem);
        }
    });

    // Função global para carregar dados para edição (GET 1 registro)
    window.editarCliente = async (id) => {
        const retorno = await fetch('php/cliente_get.php?id=' + id);
        const resposta = await retorno.json();

        if (resposta.status === 'ok') {
            const cli = resposta.data[0]; // Pega o único resultado
            document.getElementById("nome").value = cli.nome;
            document.getElementById("email").value = cli.email;
            document.getElementById("telefone").value = cli.telefone;
            document.getElementById("documento").value = cli.documento;
            document.getElementById("cliente-id").value = cli.id;
            
            btnSalvar.textContent = "Atualizar Cliente";
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    // DELETE: Excluir cliente
    window.excluirCliente = async (id) => {
        if (confirm("Deseja realmente excluir este cliente?")) {
            const retorno = await fetch('php/cliente_excluir.php?id=' + id);
            const resposta = await retorno.json();
            
            if(resposta.status === 'ok') {
                renderizarClientes();
            } else {
                alert("Erro ao excluir: " + resposta.mensagem);
            }
        }
    };

    // Inicializa carregando a tabela
    renderizarClientes();
});