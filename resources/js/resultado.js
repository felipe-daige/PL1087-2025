// ========================================
// PÁGINA DE RESULTADOS - GRÁFICOS CHART.JS
// Sistema de Inteligência Tributária
// Lei 15.270/2025 (IRPF 2026)
// ========================================

// Utilitário para formatar moeda
function formatCurrency(value) {
    const numeric = Number.isFinite(value) ? value : 0;
    const prefix = numeric < 0 ? '- ' : '';
    const formatted = Math.abs(numeric).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    return `${prefix}R$ ${formatted}`;
}

// Cores monocromáticas - azul + cinzas
const CHART_COLORS = {
    blue: 'rgba(59, 130, 246, 0.85)',
    blueMedium: 'rgba(96, 165, 250, 0.85)',
    blueLight: 'rgba(147, 197, 253, 0.85)',
    neutral: 'rgba(115, 115, 115, 0.7)',
    neutralLight: 'rgba(163, 163, 163, 0.7)',
    neutralLighter: 'rgba(212, 212, 212, 0.7)',
    green: 'rgba(34, 197, 94, 0.8)',
    red: 'rgba(239, 68, 68, 0.8)',
};

const CHART_BORDERS = {
    blue: 'rgb(59, 130, 246)',
    blueMedium: 'rgb(96, 165, 250)',
    blueLight: 'rgb(147, 197, 253)',
    neutral: 'rgb(115, 115, 115)',
    neutralLight: 'rgb(163, 163, 163)',
    neutralLighter: 'rgb(212, 212, 212)',
    green: 'rgb(34, 197, 94)',
    red: 'rgb(239, 68, 68)',
};

// ========================================
// GRÁFICO 1: COMPOSIÇÃO DA RENDA (DONUT)
// ========================================
function initIncomeChart() {
    const ctx = document.getElementById('incomeChart');
    if (!ctx || !window.chartData?.income) return;

    const data = window.chartData.income;
    const labels = [];
    const values = [];
    const colors = [];
    const borders = [];

    // Mapear dados com cores monocromáticas
    const mapping = [
        { key: 'taxable', label: 'Salários/Pró-labore', color: 'blue' },
        { key: 'rental', label: 'Aluguéis', color: 'blueMedium' },
        { key: 'dividends', label: 'Dividendos', color: 'blueLight' },
        { key: 'jcp', label: 'JCP', color: 'neutral' },
        { key: 'fii', label: 'FIIs', color: 'neutralLight' },
        { key: 'investments', label: 'Investimentos', color: 'neutralLighter' },
    ];

    mapping.forEach(item => {
        if (data[item.key] > 0) {
            labels.push(item.label);
            values.push(data[item.key]);
            colors.push(CHART_COLORS[item.color]);
            borders.push(CHART_BORDERS[item.color]);
        }
    });

    // Se não há dados, mostrar placeholder
    if (values.length === 0) {
        labels.push('Sem dados');
        values.push(1);
        colors.push(CHART_COLORS.neutral);
        borders.push(CHART_BORDERS.neutral);
    }

    new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderColor: borders,
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${formatCurrency(context.parsed)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// ========================================
// GRÁFICO 2: COMPARATIVO DE REGIMES (BAR)
// ========================================
function initRegimeChart() {
    const ctx = document.getElementById('regimeChart');
    if (!ctx || !window.chartData?.regime) return;

    const data = window.chartData.regime;

    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Simplificado', 'Deduções Legais'],
            datasets: [{
                label: 'Imposto Anual',
                data: [data.simplified || 0, data.legal || 0],
                backgroundColor: [CHART_COLORS.blue, CHART_COLORS.neutral],
                borderColor: [CHART_BORDERS.blue, CHART_BORDERS.neutral],
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.parsed.x);
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000) {
                                return 'R$ ' + (value / 1000).toFixed(0) + 'k';
                            }
                            return 'R$ ' + value;
                        }
                    }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });
}

// ========================================
// GRÁFICO 3: GAUGE IRPFM
// ========================================
function initIrpfmGauge() {
    const ctx = document.getElementById('irpfmGauge');
    if (!ctx || !window.chartData?.irpfm?.triggered) return;

    const data = window.chartData.irpfm;
    const rate = data.rate || 0;

    // Criar gráfico de gauge semi-circular
    new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Alíquota Aplicada', 'Restante'],
            datasets: [{
                data: [rate, 10 - rate],
                backgroundColor: [
                    rate <= 5 ? CHART_COLORS.blue : CHART_COLORS.red,
                    'rgba(229, 231, 235, 0.3)'
                ],
                borderWidth: 0,
                circumference: 180,
                rotation: 270
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    filter: function(tooltipItem) {
                        return tooltipItem.dataIndex === 0;
                    },
                    callbacks: {
                        label: function(context) {
                            return `Alíquota: ${context.parsed.toFixed(2)}%`;
                        }
                    }
                }
            }
        }
    });
}

// ========================================
// GRÁFICO 4: COMPARATIVO PF vs PJ (BAR)
// ========================================
function initHoldingChart() {
    const ctx = document.getElementById('holdingChart');
    if (!ctx || !window.chartData?.holding?.hasIncome) return;

    const data = window.chartData.holding;

    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Pessoa Física', 'Holding (PJ)'],
            datasets: [{
                label: 'Imposto Mensal',
                data: [data.pf || 0, data.pj || 0],
                backgroundColor: [CHART_COLORS.neutral, CHART_COLORS.blue],
                borderColor: [CHART_BORDERS.neutral, CHART_BORDERS.blue],
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.parsed.x) + '/mês';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });
}

// ========================================
// GRÁFICO 5: PROJEÇÃO 12 MESES (LINE)
// ========================================
function initProjectionChart() {
    const ctx = document.getElementById('projectionChart');
    if (!ctx || !window.chartData?.projection?.length) return;

    const projection = window.chartData.projection;
    const labels = projection.map(p => `Mês ${p.month}`);
    const pfData = projection.map(p => p.cumulativePF);
    const pjData = projection.map(p => p.cumulativePJ);

    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'PF (Acumulado)',
                    data: pfData,
                    borderColor: CHART_BORDERS.neutral,
                    backgroundColor: 'rgba(115, 115, 115, 0.05)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    borderWidth: 2
                },
                {
                    label: 'PJ (Acumulado)',
                    data: pjData,
                    borderColor: CHART_BORDERS.blue,
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000) {
                                return 'R$ ' + (value / 1000).toFixed(0) + 'k';
                            }
                            return 'R$ ' + value;
                        }
                    }
                }
            }
        }
    });
}

// ========================================
// GRÁFICO 6: BREAKDOWN DO IMPOSTO (STACKED BAR)
// ========================================
function initTaxBreakdownChart() {
    const ctx = document.getElementById('taxBreakdownChart');
    if (!ctx || !window.chartData?.taxBreakdown) return;

    const data = window.chartData.taxBreakdown;
    
    // Preparar dados
    const datasets = [];
    const values = [];
    const labels = [];
    const colors = [];
    const borders = [];

    if (data.base > 0) {
        labels.push('IRPF Base');
        values.push(data.base);
        colors.push(CHART_COLORS.blue);
        borders.push(CHART_BORDERS.blue);
    }

    if (data.dividends > 0) {
        labels.push('Dividendos');
        values.push(data.dividends);
        colors.push(CHART_COLORS.blueMedium);
        borders.push(CHART_BORDERS.blueMedium);
    }

    if (data.irpfm > 0) {
        labels.push('IRPFM');
        values.push(data.irpfm);
        colors.push(CHART_COLORS.neutral);
        borders.push(CHART_BORDERS.neutral);
    }

    // Se não houver impostos, mostrar placeholder
    if (values.length === 0) {
        labels.push('Sem imposto');
        values.push(0);
        colors.push(CHART_COLORS.neutralLighter);
        borders.push(CHART_BORDERS.neutralLighter);
    }

    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Composição do Imposto'],
            datasets: labels.map((label, index) => ({
                label: label,
                data: [values[index]],
                backgroundColor: colors[index],
                borderColor: borders[index],
                borderWidth: 2,
                borderRadius: 4
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { display: false }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000) {
                                return 'R$ ' + (value / 1000).toFixed(0) + 'k';
                            }
                            return 'R$ ' + value;
                        }
                    }
                }
            }
        }
    });
}

// ========================================
// GRÁFICO 7: RENDA TOTAL VS. IMPOSTO TOTAL
// ========================================
function initIncomeVsTaxChart() {
    const ctx = document.getElementById('incomeVsTaxChart');
    if (!ctx || !window.chartData?.incomeVsTax) return;

    const data = window.chartData.incomeVsTax;
    
    // Formatar valores para exibição
    const formatValue = (value) => {
        if (value >= 1000000) {
            return 'R$ ' + (value / 1000000).toFixed(1) + 'M';
        } else if (value >= 1000) {
            return 'R$ ' + (value / 1000).toFixed(0) + 'k';
        }
        return 'R$ ' + value.toFixed(0);
    };

    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Renda Total', 'Imposto Total'],
            datasets: [
                {
                    label: 'Valor',
                    data: [data.income, data.tax],
                    backgroundColor: [CHART_COLORS.blue, CHART_COLORS.red],
                    borderColor: [CHART_BORDERS.blue, CHART_BORDERS.red],
                    borderWidth: 2,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        generateLabels: function(chart) {
                            return [
                                {
                                    text: 'Renda Total',
                                    fillStyle: CHART_COLORS.blue,
                                    strokeStyle: CHART_BORDERS.blue,
                                    hidden: false,
                                    index: 0
                                },
                                {
                                    text: 'Imposto Total',
                                    fillStyle: CHART_COLORS.red,
                                    strokeStyle: CHART_BORDERS.red,
                                    hidden: false,
                                    index: 1
                                }
                            ];
                        },
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.y || context.parsed;
                            return context.dataset.label + ': ' + formatCurrency(value);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        color: '#525252'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { 
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'R$ ' + (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return 'R$ ' + (value / 1000).toFixed(0) + 'k';
                            }
                            return 'R$ ' + value;
                        },
                        font: {
                            size: 11
                        },
                        color: '#737373'
                    }
                }
            }
        }
    });
}

// ========================================
// NAVEGAÇÃO DAS TABS
// ========================================
function showTab(tabName) {
    // Esconder todos os conteúdos
    document.querySelectorAll('.result-content').forEach(el => el.classList.add('hidden'));
    
    // Remover estilo ativo de todas as abas
    document.querySelectorAll('.result-tab').forEach(el => {
        el.classList.remove('text-blue-600', 'border-blue-500');
        el.classList.add('text-neutral-500', 'border-transparent');
    });
    
    // Mostrar conteúdo selecionado
    const content = document.getElementById('content-' + tabName);
    if (content) content.classList.remove('hidden');
    
    // Ativar aba selecionada
    const activeTab = document.getElementById('tab-' + tabName);
    if (activeTab) {
        activeTab.classList.remove('text-neutral-500', 'border-transparent');
        activeTab.classList.add('text-blue-600', 'border-blue-500');
    }
}

// Expor função globalmente para onclick
window.showTab = showTab;

// ========================================
// INICIALIZAÇÃO
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar todos os gráficos
    initIncomeChart();
    initRegimeChart();
    initIrpfmGauge();
    initHoldingChart();
    initProjectionChart();
    initTaxBreakdownChart();
    initIncomeVsTaxChart();
});

// ========================================
// IMPRESSÃO
// ========================================
window.addEventListener('beforeprint', () => {
    // Ajustar layout para impressão
    document.querySelectorAll('.chart-container').forEach(container => {
        container.style.height = '200px';
    });
});

window.addEventListener('afterprint', () => {
    // Restaurar layout após impressão
    document.querySelectorAll('.chart-container').forEach(container => {
        container.style.height = '';
    });
});
