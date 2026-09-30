document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("login-form");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const fd = new FormData();
        fd.append('email', document.getElementById('email').value);
        fd.append('senha', document.getElementById('password').value);

        try {
            const retorno = await fetch('php/usuario_login.php', {
                method: 'POST',
                body: fd
            });
            const resposta = await retorno.json();

            if (resposta.status === 'ok') {
                window.location.href = "dashboard.html";
            } else {
                alert(resposta.mensagem);
            }
        } catch (error) {
            console.error("Erro no login:", error);
            alert("Falha na conexão com o servidor.");
        }
    });
});