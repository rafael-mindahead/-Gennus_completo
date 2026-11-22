document.addEventListener('DOMContentLoaded', async () => {
    try {
        const snapshot = await Gennus.finance.load();
        const fmt = value => value.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
        document.getElementById('r-bruta').textContent = fmt(snapshot.totals.receita);
        document.getElementById('r-liquida').textContent = fmt(snapshot.totals.liquida);
    } catch (error) {
        Gennus.report(error);
    }
});
