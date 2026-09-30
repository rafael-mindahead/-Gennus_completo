'use strict';

Gennus.finance = {
    totals(vendas, funcionarios, despesas) {
        const receita = vendas.reduce((sum, venda) => sum + Number(venda.valor_total), 0);
        const estoque = vendas.reduce((sum, venda) => sum + Number(venda.custo_total), 0);
        const salarios = funcionarios.filter(func => func.status === 'Ativo').reduce((sum, func) => sum + Number(func.salario_base), 0);
        const manuais = despesas.reduce((sum, despesa) => sum + Number(despesa.valor), 0);
        return {receita, estoque, salarios, manuais, despesas: estoque + salarios + manuais, liquida: receita - estoque - salarios - manuais};
    },
    localDate(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    },
    daily(vendas, today = new Date()) {
        const values = {};
        for (let offset = 6; offset >= 0; offset--) {
            const date = new Date(today);
            date.setDate(date.getDate() - offset);
            values[this.localDate(date)] = 0;
        }
        vendas.forEach(venda => {
            const date = String(venda.data_venda).slice(0, 10);
            if (Object.hasOwn(values, date)) values[date] += Number(venda.valor_total);
        });
        return {labels: Object.keys(values).map(date => `${date.slice(8, 10)}/${date.slice(5, 7)}`), values: Object.values(values)};
    },
    async load() {
        const endpoints = ['venda', 'funcionario', 'despesa'];
        const results = await Promise.all(endpoints.map(async name => (await (await Gennus.fetch(`/php/${name}_get.php`)).json()).data));
        return {vendas: results[0], funcionarios: results[1], despesas: results[2], totals: this.totals(...results)};
    }
};
