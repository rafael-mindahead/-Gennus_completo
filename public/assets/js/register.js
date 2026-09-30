// Javascript/register.js
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("register-form");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const fd = new FormData();
        fd.append('nome', document.getElementById('reg-nome').value);
        fd.append('sobrenome', document.getElementById('reg-sobrenome').value);
        fd.append('email_corporativo', document.getElementById('reg-email').value);
        fd.append('telefone', document.getElementById('reg-tel').value);
        fd.append('cpf_cnpj', document.getElementById('reg-documento').value);
        fd.append('senha', document.getElementById('reg-password').value);

        try {
            const retorno = await fetch('php/usuario_novo.php', {
                method: 'POST',
                body: fd
            });
            const resposta = await retorno.json();

            if (resposta.status === 'ok') {
                alert(resposta.mensagem);
                window.location.href = 'login.html'; // Manda pro login
            } else {
                alert("Erro: " + resposta.mensagem);
            }
        } catch (error) {
            console.error("Erro no cadastro:", error);
            alert("Falha na conexão com o servidor.");
        }
    });
});