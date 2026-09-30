document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('/php/valida_sessao.php');
        const result = await response.json();
        if (result.status !== 'ok') {
            window.location.href = '/pages/auth/login.html';
            return;
        }
        const user = result.data;
        document.getElementById('edit-name').value = `${user.nome} ${user.sobrenome || ''}`.trim();
        document.getElementById('edit-email').value = user.email_corporativo;
        document.getElementById('profile-form').addEventListener('submit', async event => {
            event.preventDefault();
            try {
                const parts = document.getElementById('edit-name').value.trim().split(/\s+/);
                const form = new FormData();
                form.append('nome', parts.shift() || '');
                form.append('sobrenome', parts.join(' '));
                form.append('senha', document.getElementById('edit-password').value);
                const response = await fetch('/php/usuario_alterar.php', {method: 'POST', body: form});
                const result = await response.json();
                if (result.status !== 'ok') throw new Error(result.mensagem);
                alert(result.mensagem);
                window.location.href = '/pages/erp/dashboard.html';
            } catch (error) {
                alert(error.message || 'Falha ao salvar o perfil.');
            }
        });
    } catch (error) {
        alert('Falha ao carregar o perfil.');
    }
});
