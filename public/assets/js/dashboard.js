document.addEventListener("DOMContentLoaded", async () => {
    
    // 1. VALIDAÇÃO DE SESSÃO E CARREGAMENTO DO USUÁRIO
    try {
        const respSessao = await Gennus.fetch('/php/valida_sessao.php');
        const jsonSessao = await respSessao.json();

        if (jsonSessao.status === 'nok') {
            // Se não estiver logado, chuta pro login
            window.location.href = '/pages/auth/login.html';
            return;
        }

        // Preenche os dados do usuário na tela
        const user = jsonSessao.data; 
        // Assumindo que a coluna no seu banco seja "nome"
        document.getElementById("side-user-name").textContent = user.nome;
        document.getElementById("user-initials").textContent = user.nome.charAt(0).toUpperCase();
        document.getElementById("welcome-title").textContent = `Dashboard — ${user.nome}`;

    } catch (error) {
        Gennus.report(error);
        return;
    }

    // 3. BUSCAR DADOS REAIS DO BANCO DE DADOS EM PARALELO
    let vendas = [];
    let funcs = [];
    let manuais = [];

    try {
        const [resVendas, resFuncs, resManuais] = await Promise.all([
            Gennus.fetch('/php/venda_get.php').then(r => r.json()),
            Gennus.fetch('/php/funcionario_get.php').then(r => r.json()),
            Gennus.fetch('/php/despesa_get.php').then(r => r.json())
        ]);

        if (resVendas.status === 'ok') vendas = resVendas.data;
        if (resFuncs.status === 'ok') funcs = resFuncs.data;
        if (resManuais.status === 'ok') manuais = resManuais.data;

    } catch (error) {
        Gennus.report(error);
        return;
    }

    // 4. CÁLCULOS
    const receitaTotal = vendas.reduce((acc, v) => acc + (parseFloat(v.valor_total) || 0), 0);
    const custoEstoque = vendas.reduce((acc, v) => acc + (parseFloat(v.custo_total) || 0), 0);
    
    // O banco de dados retorna string, então garantimos o parseFloat
    const totalSalarios = funcs.filter(f => f.status === "Ativo").reduce((acc, f) => acc + (parseFloat(f.salario_base) || 0), 0);
    const outrosGastos = manuais.reduce((acc, g) => acc + (parseFloat(g.valor) || 0), 0);

    const despesasTotais = custoEstoque + totalSalarios + outrosGastos;
    const lucroLiquido = receitaTotal - despesasTotais;

    const fmt = (n) => n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    
    document.getElementById("dash-bruta").textContent = fmt(receitaTotal);
    document.getElementById("dash-despesa").textContent = fmt(despesasTotais);
    document.getElementById("dash-liquida").textContent = fmt(lucroLiquido);

    // 5. GRÁFICO DE PIZZA (SAÍDAS)
    const ctxPizza = document.getElementById('chartPizza');
    if (ctxPizza) {
        new Chart(ctxPizza, {
            type: 'doughnut',
            data: {
                labels: ['Estoque', 'Equipe', 'Manuais'],
                datasets: [{
                    // Adicionado um pequeno ternário para o gráfico não sumir se tudo for zero
                    data: [custoEstoque, totalSalarios, outrosGastos],
                    backgroundColor: ['#a855f7', '#7e22ce', '#3b0764'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#666', font: { size: 10 } } }
                }
            }
        });
    }

    // 6. GRÁFICO DE LINHA (PERFORMANCE SIMPLIFICADA)
    const ctxLinha = document.getElementById('chartLinha');
    if (ctxLinha) {
        new Chart(ctxLinha, {
            type: 'line',
            data: {
                labels: Gennus.finance.daily(vendas).labels,
                datasets: [{
                    label: 'Vendas por dia (R$)',
                    data: Gennus.finance.daily(vendas).values,
                    borderColor: '#a855f7',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#666' } },
                    x: { grid: { display: false }, ticks: { color: '#666' } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});