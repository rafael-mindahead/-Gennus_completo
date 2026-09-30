'use strict';

window.Gennus = {
    escape(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character]));
    },
    report(error) {
        console.error(error);
        alert(error?.message || 'Não foi possível concluir a operação.');
    },
    async fetch(url, options = {}) {
        let response;
        let data;
        try {
            response = await window.fetch(url, {...options, credentials: 'same-origin'});
            data = await response.clone().json();
        } catch (error) {
            throw new Error('Falha na conexão ou resposta inválida do servidor.');
        }
        if (!response.ok || data.status !== 'ok') {
            throw new Error(data.mensagem || 'Não foi possível concluir a operação.');
        }
        return response;
    }
};

window.addEventListener('unhandledrejection', event => {
    event.preventDefault();
    Gennus.report(event.reason);
});
