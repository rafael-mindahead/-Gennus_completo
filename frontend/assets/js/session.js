'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const loginPath = '/pages/auth/login.html';

    // Apenas controles de saída encerram a sessão. Links públicos de login não são logout.
    document.querySelectorAll('[data-logout]').forEach(link => {
        link.addEventListener('click', async event => {
            event.preventDefault();

            if (link.dataset.loggingOut === 'true') return;
            link.dataset.loggingOut = 'true';
            link.setAttribute('aria-disabled', 'true');

            try {
                await Gennus.fetch('/php/cliente_logoff.php', {method: 'POST'});
                window.location.replace(loginPath);
            } catch (error) {
                Gennus.report(error);
            } finally {
                delete link.dataset.loggingOut;
                link.removeAttribute('aria-disabled');
            }
        });
    });

    // Páginas HTML são estáticas: a autorização de dados deve permanecer no backend.
    (async () => {
        try {
            const response = await window.fetch('/php/valida_sessao.php', {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {'Accept': 'application/json'}
            });

            if (response.status === 401) {
                window.location.replace(loginPath);
                return;
            }

            if (!response.ok) throw new Error('Não foi possível validar a sessão.');

            const result = await response.json();
            if (result.status !== 'ok') {
                throw new Error(result.mensagem || 'Sessão inválida.');
            }

            const nome = result.data?.nome;
            if (typeof nome === 'string' && nome.trim()) {
                const nameNode = document.getElementById('side-user-name');
                const initialsNode = document.getElementById('user-initials');
                if (nameNode) nameNode.textContent = nome;
                if (initialsNode) initialsNode.textContent = nome.trim().charAt(0).toUpperCase();
            }
        } catch (error) {
            Gennus.report(error);
        }
    })();
});
