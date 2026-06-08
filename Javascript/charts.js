document.addEventListener("DOMContentLoaded", async () => {

    // 1. VALIDAÇÃO DE SESSÃO
    // Impede que alguém acesse os relatórios sem estar logado
    try {
        const respSessao = await fetch('php/valida_sessao.php');
        const jsonSessao = await respSessao.json();
        if (jsonSessao.status === 'nok') {
            window.location.href = 'login.html';
            return;
        }
    } catch (error) {
        console.error("Erro na sessão:", error);
    }

    // 2. RECUPERAÇÃO DE DADOS EM PARALELO DO PHP
    let vendas = [];
    let funcs = [];
    let manuais = [];
    let produtos = [];

    try {
        const [resVendas, resFuncs, resManuais, resProds] = await Promise.all([
            fetch('php/venda_get.php').then(r => r.json()),
            fetch('php/funcionario_get.php').then(r => r.json()),
            fetch('php/despesa_get.php').then(r => r.json()),
            fetch('php/produto_get.php').then(r => r.json())
        ]);

        if (resVendas.status === 'ok') vendas = resVendas.data;
        if (resFuncs.status === 'ok') funcs = resFuncs.data;
        if (resManuais.status === 'ok') manuais = resManuais.data;
        if (resProds.status === 'ok') produtos = resProds.data;

    } catch (error) {
        console.error("Erro ao carregar dados para os gráficos:", error);
    }

    // 3. CÁLCULOS TOTAIS (convertendo strings do banco para Float)
    const totalVendas = vendas.reduce((acc, v) => acc + (parseFloat(v.valor_total) || 0), 0);
    const custoProd = vendas.reduce((acc, v) => acc + (parseFloat(v.custo_total) || 0), 0);
    const salarios = funcs.reduce((acc, f) => acc + (parseFloat(f.salario_base) || 0), 0);
    const totalManuais = manuais.reduce((acc, g) => acc + (parseFloat(g.valor) || 0), 0);

    // 1. GRÁFICO DOUGHNUT (CUSTOS)
    new Chart(document.getElementById('chartCustos'), {
        type: 'doughnut',
        data: {
            labels: ['Estoque', 'Salários', 'Manuais'],
            datasets: [{
                // Tratativa para não quebrar o gráfico se for 0
                data: [custoProd || 0.1, salarios || 0.1, totalManuais || 0.1],
                backgroundColor: ['#a855f7', '#7e22ce', '#3b0764'],
                borderWidth: 0,
                cutout: '80%'
            }]
        },
        options: { plugins: { legend: { position: 'bottom', labels: { color: '#666' } } } }
    });

    // 2. GRÁFICO DE LINHA (FLUXO - 7 DIAS)
    const ultimos7Dias = {};
    for (let i = 6; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        // Usa o formato ISO curto: YYYY-MM-DD para bater com o banco
        const isoDate = d.toISOString().split('T')[0]; 
        ultimos7Dias[isoDate] = 0;
    }
    
    vendas.forEach(v => {
        // Pega só a parte da data (YYYY-MM-DD) cortando a hora
        const dV = v.data_venda ? v.data_venda.split(' ')[0] : null; 
        if (dV && ultimos7Dias.hasOwnProperty(dV)) {
            ultimos7Dias[dV] += parseFloat(v.valor_total);
        }
    });

    // Formata os rótulos do eixo X para ficar bonito no estilo DD/MM
    const labelsFormatadas = Object.keys(ultimos7Dias).map(dt => {
        const partes = dt.split('-');
        return `${partes[2]}/${partes[1]}`;
    });

    new Chart(document.getElementById('chartFluxo'), {
        type: 'line',
        data: {
            labels: labelsFormatadas,
            datasets: [{
                label: 'Vendas (R$)',
                data: Object.values(ultimos7Dias),
                borderColor: '#a855f7',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { grid: { color: 'rgba(255,255,255,0.05)' } }, x: { grid: { display: false } } } }
    });

    // 3. GRÁFICO DE BARRAS (CATEGORIAS)
    const catMap = {};
    vendas.forEach(v => {
        // Encontra o produto vendido no array de produtos pelo ID
        const pOriginal = produtos.find(p => p.id == v.produto_id);
        const cat = pOriginal && pOriginal.categoria ? pOriginal.categoria : 'Outros';
        catMap[cat] = (catMap[cat] || 0) + parseFloat(v.valor_total);
    });

    new Chart(document.getElementById('chartCategorias'), {
        type: 'bar',
        data: {
            labels: Object.keys(catMap),
            datasets: [{
                data: Object.values(catMap),
                backgroundColor: '#a855f7',
                borderRadius: 5
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // 4. GRÁFICO RADAR (SAÚDE OPERACIONAL)
    const scoreVendas = Math.min(100, (totalVendas / 5000) * 100);
    const scoreEquipe = Math.min(100, (funcs.length / 5) * 100);
    const scoreEstoque = Math.min(100, (produtos.length / 10) * 100);
    const scoreMargem = totalVendas > 0 ? ((totalVendas - (custoProd + salarios + totalManuais)) / totalVendas) * 100 : 0;

    new Chart(document.getElementById('chartRadar'), {
        type: 'radar',
        data: {
            labels: ['Vendas', 'Equipe', 'Estoque', 'Margem', 'Manuais'],
            datasets: [{
                label: 'Score Operacional',
                data: [scoreVendas || 0, scoreEquipe || 0, scoreEstoque || 0, Math.max(0, scoreMargem), 50],
                backgroundColor: 'rgba(168, 85, 247, 0.2)',
                borderColor: '#a855f7',
                pointBackgroundColor: '#a855f7'
            }]
        },
        options: {
            scales: {
                r: {
                    angleLines: { color: 'rgba(255,255,255,0.05)' },
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    pointLabels: { color: '#666' },
                    ticks: { display: false }
                }
            },
            plugins: { legend: { display: false } }
        }
    });

    // ATUALIZAR INSIGHTS
    const fmt = (n) => n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('proj-lucro').textContent = fmt(totalVendas * 1.2); // Projeção +20%
});