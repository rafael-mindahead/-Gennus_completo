
'use strict';

window.Gennus = {
    escape(value) {
        return String(value ?? '').replace(
            /[&<>"']/g,
            character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[character])
        );
    },

    report(error) {
        console.error(error);
        alert(
            error?.message ||
            'Não foi possível concluir a operação.'
        );
    },

    async fetch(url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();

        const headers = {
            ...(options.headers || {}),
            'Accept': 'application/json'
        };

        if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
            const csrfResponse = await window.fetch(
                '/php/csrf.php',
                {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!csrfResponse.ok) {
                throw new Error('Falha ao obter token CSRF.');
            }

            const csrfData = await csrfResponse.json();

            if (!csrfData.data?.csrf_token) {
                throw new Error('Token CSRF não encontrado.');
            }

            headers['X-CSRF-TOKEN'] =
                csrfData.data.csrf_token;
        }

        let response;
        let data;

        try {
            response = await window.fetch(url, {
                ...options,
                method,
                headers,
                credentials: 'same-origin'
            });

            data = await response.clone().json();
        } catch (error) {
            throw new Error(
                'Falha na conexão ou resposta inválida do servidor.'
            );
        }

        if (!response.ok || data.status !== 'ok') {
            throw new Error(
                data.mensagem ||
                data.message ||
                'Não foi possível concluir a operação.'
            );
        }

        return response;
    }
};

window.addEventListener('unhandledrejection', event => {
    event.preventDefault();
    Gennus.report(event.reason);
});
