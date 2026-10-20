'use strict';

document.addEventListener('DOMContentLoaded', async () => {
    document.querySelectorAll('a[href="/pages/auth/login.html"], .logout-link, [data-logout]').forEach(link => {
        link.addEventListener('click', async event => {
            event.preventDefault();
            try {
                await Gennus.fetch('/php/cliente_logoff.php', {method: 'POST'});
                window.location.href = '/pages/auth/login.html';
            } catch (error) {
                Gennus.report(error);
            }
        });
    });
    try {
        const response = await window.fetch('/php/valida_sessao.php');
        const result = await response.json();
        if (response.status === 401) {
            window.location.href = '/pages/auth/login.html';
            return;
        }
        if (!response.ok || result.status !== 'ok') throw new Error(result.mensagem || 'Falha ao validar a sessão.');
    } catch (error) {
        Gennus.report(error);
    }
});
