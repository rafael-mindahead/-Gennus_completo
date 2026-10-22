'use strict';

const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = readFileSync(path.join(__dirname, '../../frontend/assets/js/session.js'), 'utf8');
const LOGIN = '/pages/auth/login.html';

function createHarness({status = 200, logoutFails = false, usuario = {nome: 'Ana'}} = {}) {
    const redirects = [];
    const errors = [];
    const calls = [];
    const elements = {
        'side-user-name': {textContent: ''},
        'user-initials': {textContent: ''}
    };
    let ready;
    let click;
    const link = {
        dataset: {},
        attributes: {},
        setAttribute(key, value) { this.attributes[key] = value; },
        removeAttribute(key) { delete this.attributes[key]; },
        addEventListener(event, fn) { if (event === 'click') click = fn; }
    };
    const document = {
        addEventListener(event, fn) { if (event === 'DOMContentLoaded') ready = fn; },
        querySelectorAll(selector) {
            assert.equal(selector, '[data-logout]');
            return [link];
        },
        getElementById(id) { return elements[id] || null; }
    };
    const window = {
        fetch: async (url, options) => {
            calls.push({url, options});
            return {
                status,
                ok: status >= 200 && status < 300,
                json: async () => ({status: 'ok', data: usuario})
            };
        },
        location: {replace(url) { redirects.push(url); }}
    };
    const Gennus = {
        async fetch(url, options) {
            calls.push({url, options});
            if (logoutFails) throw new Error('Erro no servidor');
            return {ok: true};
        },
        report(error) { errors.push(error.message); }
    };
    vm.runInNewContext(source, {document, window, Gennus});
    ready();
    return {redirects, errors, calls, elements, click: (e) => click(e), link};
}

test('logout envia POST e redireciona apenas depois do sucesso', async () => {
    const h = createHarness();
    await h.click({preventDefault() {}});
    assert.equal(h.calls.filter(c => c.url === '/php/cliente_logoff.php').length, 1);
    assert.equal(h.calls.find(c => c.url === '/php/cliente_logoff.php').options.method, 'POST');
    assert.deepEqual(h.redirects, [LOGIN]);
});

test('falha de logout mantém usuário na página e reporta erro', async () => {
    const h = createHarness({logoutFails: true});
    await h.click({preventDefault() {}});
    assert.deepEqual(h.redirects, []);
    assert.equal(h.errors.length, 1);
    assert.equal(h.link.dataset.loggingOut, undefined);
});

test('sessão inválida redireciona ao login', async () => {
    const h = createHarness({status: 401});
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(h.redirects, [LOGIN]);
});

test('sessão válida preenche nome e iniciais sem HTML', async () => {
    const h = createHarness({usuario: {nome: 'Ana Silva'}});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(h.elements['side-user-name'].textContent, 'Ana Silva');
    assert.equal(h.elements['user-initials'].textContent, 'A');
    assert.deepEqual(h.errors, []);
});
