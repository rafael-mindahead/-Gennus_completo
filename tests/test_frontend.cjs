const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const events = {};
const window = {addEventListener: (name, cb) => {events[name] = cb;}};
const context = vm.createContext({window, console, alert() {}, Error, Date, Number, Object});
vm.runInContext(fs.readFileSync(path.join(__dirname, '../public/assets/js/common.js'), 'utf8'), context);
context.Gennus = window.Gennus;
vm.runInContext(fs.readFileSync(path.join(__dirname, '../public/assets/js/finance.js'), 'utf8'), context);
const Gennus = window.Gennus;

test('escape stored markup in both text and quoted attributes', () => {
    assert.equal(Gennus.escape('<img src=x onerror="alert(1)">&\''), '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;&amp;&#39;');
    assert.equal(Gennus.escape(null), '');
});
test('HTTP and business errors propagate; empty successful lists remain valid', async () => {
    window.fetch = async () => ({ok: false, clone: () => ({json: async () => ({status: 'nok', mensagem: 'Estoque insuficiente.'})})});
    await assert.rejects(Gennus.fetch('/php/venda_novo.php'), /Estoque insuficiente/);
    window.fetch = async () => {throw new Error('offline');};
    await assert.rejects(Gennus.fetch('/php/produto_get.php'), /Falha na conexão/);
    window.fetch = async () => ({ok: true, clone: () => ({json: async () => ({status: 'ok', data: []})})});
    assert.ok(await Gennus.fetch('/php/produto_get.php'));
});
test('finance totals include cost of sold goods and only active salaries', () => {
    const totals = Gennus.finance.totals([{valor_total: 100, custo_total: 30}], [{status: 'Ativo', salario_base: 20}, {status: 'Inativo', salario_base: 999}], [{valor: 5}]);
    assert.equal(totals.despesas, 55);
    assert.equal(totals.liquida, 45);
});
test('daily chart uses local calendar date at a UTC day boundary', () => {
    process.env.TZ = 'America/Sao_Paulo';
    const today = new Date('2026-09-30T01:00:00Z');
    assert.equal(Gennus.finance.localDate(today), '2026-09-29');
    const values = Gennus.finance.daily([{data_venda: '2026-09-29 19:00:00', valor_total: 25}, {data_venda: '2026-09-30 00:00:00', valor_total: 80}], today);
    assert.equal(values.values.at(-1), 25);
    assert.equal(values.labels.at(-1), '29/09');
});
