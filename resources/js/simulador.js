// ========================================
// SISTEMA DE INTELIGÊNCIA TRIBUTÁRIA
// Lei 15.270/2025 (IRPF 2026)
// ========================================

import { Chart } from 'chart.js/auto';

let comparisonChart = null;
let irpfmGauge = null;
let holdingChart = null;

// ========================================
// FUNÇÕES UTILITÁRIAS
// ========================================

function parseInputNumber(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }
    // Se já é um número, retornar diretamente
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
    }
    // Converter string para número (sem máscara, campos são type="number" agora)
    const parsed = parseFloat(value);
    return Number.isFinite(parsed) ? parsed : 0;
}

function formatCurrency(value) {
    const numeric = Number.isFinite(value) ? value : 0;
    const prefix = numeric < 0 ? '- ' : '';
    const formatted = Math.abs(numeric).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    return `${prefix}R$ ${formatted}`;
}

/**
 * Remove a formatação de moeda brasileira e retorna o valor numérico
 * @param {string|number} value - Valor formatado (ex: "R$ 1.234,56" ou "1.234,56") ou número
 * @returns {number} - Valor numérico
 */
function removeCurrencyMask(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }
    // Se já é um número, retornar diretamente
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
    }
    
    // Converter para string e remover formatação
    let cleaned = String(value)
        .replace(/R\$\s*/g, '')  // Remove "R$" e espaços
        .replace(/\s/g, '')      // Remove todos os espaços
        .trim();
    
    // Se estiver vazio após limpeza, retornar 0
    if (!cleaned) return 0;
    
    // Verificar se tem vírgula (formato brasileiro) ou ponto (formato internacional)
    const hasComma = cleaned.includes(',');
    const hasDot = cleaned.includes('.');
    
    if (hasComma && hasDot) {
        // Formato brasileiro: 1.234,56 - remover pontos (milhar) e substituir vírgula por ponto
        cleaned = cleaned.replace(/\./g, '').replace(',', '.');
    } else if (hasComma && !hasDot) {
        // Apenas vírgula: 1234,56 - substituir vírgula por ponto
        cleaned = cleaned.replace(',', '.');
    } else if (!hasComma && hasDot) {
        // Apenas ponto: pode ser 1234.56 (decimal) ou 1.234 (milhar sem decimal)
        // Se tiver mais de um ponto, é formato de milhar, remover todos
        const dotCount = (cleaned.match(/\./g) || []).length;
        if (dotCount > 1) {
            // Formato de milhar: 1.234.567 - remover todos os pontos
            cleaned = cleaned.replace(/\./g, '');
        }
        // Caso contrário, manter o ponto como decimal
    }
    
    // Remover qualquer caractere não numérico exceto ponto decimal
    cleaned = cleaned.replace(/[^\d.]/g, '');
    
    const parsed = parseFloat(cleaned);
    return Number.isFinite(parsed) ? parsed : 0;
}

/**
 * Aplica máscara de moeda brasileira em um input
 * @param {HTMLInputElement} input - Elemento input a ser formatado
 */
function applyCurrencyMask(input) {
    if (!input) return;
    
    // Obter valor atual
    let value = input.value;
    
    // Remover tudo exceto números e vírgula (não processar pontos como decimais)
    let cleaned = value.replace(/[^\d,]/g, '');
    
    // Se não há nada, limpar
    if (!cleaned) {
        input.value = '';
        return;
    }
    
    // Verificar se há vírgula (separador decimal)
    const hasComma = cleaned.includes(',');
    
    let integerPart = '';
    let decimalPart = '';
    
    if (hasComma) {
        // Se há vírgula, dividir em parte inteira e decimal
        const commaIndex = cleaned.indexOf(',');
        integerPart = cleaned.substring(0, commaIndex).replace(/\D/g, '');
        // Pegar apenas os 2 primeiros dígitos após a vírgula
        decimalPart = cleaned.substring(commaIndex + 1).replace(/\D/g, '').substring(0, 2);
    } else {
        // Se não há vírgula, tratar tudo como parte inteira
        integerPart = cleaned.replace(/\D/g, '');
        decimalPart = '';
    }
    
    // Se não há parte inteira, usar '0'
    if (!integerPart) {
        integerPart = '0';
    }
    
    // Adicionar separadores de milhar na parte inteira
    const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
    // Montar valor formatado
    let formattedValue = formattedInteger;
    if (decimalPart) {
        formattedValue += ',' + decimalPart;
    } else if (hasComma) {
        // Se o usuário digitou vírgula mas não completou os decimais
        formattedValue += ',';
    }
    
    // Atualizar o valor do input
    input.value = formattedValue;
}

/**
 * Configura a máscara de moeda em um input
 * @param {HTMLInputElement} input - Elemento input a ser configurado
 */
function setupCurrencyMask(input) {
    if (!input) return;
    
    // Aplicar máscara ao digitar
    input.addEventListener('input', function(e) {
        applyCurrencyMask(e.target);
    });
    
    // Aplicar máscara ao perder o foco (garantir formatação completa)
    input.addEventListener('blur', function(e) {
        const currentValue = e.target.value;
        const numericValue = removeCurrencyMask(currentValue);
        
        if (numericValue > 0) {
            // Verificar se o valor já tem decimais (vírgula ou ponto)
            const hasDecimals = currentValue.includes(',') || (currentValue.includes('.') && currentValue.split('.').length > 2);
            
            if (hasDecimals) {
                // Se já tem decimais, formatar mantendo os decimais (máximo 2)
                const formatted = numericValue.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
                e.target.value = formatted;
            } else {
                // Se não tem decimais, formatar apenas com separadores de milhar (sem forçar decimais)
                const formatted = numericValue.toLocaleString('pt-BR', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                });
                e.target.value = formatted;
            }
        } else if (currentValue.trim() === '' || numericValue === 0) {
            e.target.value = '';
        }
    });
    
    // Aplicar máscara ao colar (paste)
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const numericValue = removeCurrencyMask(pastedText);
        if (numericValue > 0) {
            e.target.value = numericValue.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
            // Disparar evento input para atualizar cálculos
            e.target.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
    
    // Permitir navegação com setas e delete/backspace
    input.addEventListener('keydown', function(e) {
        // Permitir: Backspace, Delete, Tab, Escape, Enter, setas, Home, End
        if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].indexOf(e.keyCode) !== -1 ||
            // Permitir: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true)) {
            return;
        }
        // Garantir que é um número ou vírgula/ponto
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105) && e.keyCode !== 188 && e.keyCode !== 190) {
            e.preventDefault();
        }
    });
}

// ========================================
// CÁLCULOS IRPF - Lei 15.270/2025
// ========================================

/**
 * Calcula imposto progressivo IRPF anual
 * @see Lei 15.270/2025 - Art. 3º-A
 */
function calculateProgressiveTaxIRPF(annualBase) {
    if (!Number.isFinite(annualBase) || annualBase <= 0) {
        return 0;
    }

    const monthlyBase = annualBase / 12;
    let monthlyTax = calculateMonthlyTaxIRPF(monthlyBase);

    return Math.max(0, monthlyTax * 12);
}

/**
 * Calcula imposto progressivo IRPF mensal com redução do Art. 3º-A
 * @see Lei 15.270/2025 - Art. 3º-A
 */
function calculateMonthlyTaxIRPF(monthlyBase) {
    if (!Number.isFinite(monthlyBase) || monthlyBase <= 0) {
        return 0;
    }

    // Tabela progressiva
    let grossTax = 0;
    if (monthlyBase <= 5000) {
        grossTax = 0;
    } else if (monthlyBase <= 7350) {
        grossTax = Math.max(0, (monthlyBase * 0.075) - 375);
    } else if (monthlyBase <= 9250) {
        grossTax = Math.max(0, (monthlyBase * 0.15) - 926.25);
    } else if (monthlyBase <= 12000) {
        grossTax = Math.max(0, (monthlyBase * 0.225) - 1620);
    } else {
        grossTax = Math.max(0, (monthlyBase * 0.275) - 2220);
    }

    // Art. 3º-A - Redução de imposto
    const reduction = calculateTaxReduction(monthlyBase, grossTax);
    
    return Math.max(0, grossTax - reduction);
}

/**
 * Calcula a redução de imposto conforme Art. 3º-A
 * @see Lei 15.270/2025 - Art. 3º-A, §1º
 */
function calculateTaxReduction(monthlyBase, grossTax) {
    // Até R$ 5.000: Isenção total
    if (monthlyBase <= 5000) {
        return Math.min(312.89, grossTax);
    }
    
    // De R$ 5.000 a R$ 7.350: Redução linear decrescente
    if (monthlyBase <= 7350) {
        const reduction = 978.62 - (0.133145 * monthlyBase);
        return Math.max(0, Math.min(reduction, grossTax));
    }
    
    // Acima de R$ 7.350: Sem redução
    return 0;
}

// ========================================
// REPEATER: FONTES DE RENDA
// ========================================

function collectIncomeSources() {
    const container = document.getElementById('incomeSourcesContainer');
    if (!container) return [];
    
    const rows = container.querySelectorAll('.income-source-row');
    const sources = [];
    
    rows.forEach((row) => {
        const index = row.getAttribute('data-index');
        const typeSelect = row.querySelector(`select[name="incomeSources[${index}][type]"]`);
        const nameInput = row.querySelector(`input[name="incomeSources[${index}][name]"]`);
        const grossInput = row.querySelector(`input[name="incomeSources[${index}][gross]"]`);
        const inssInput = row.querySelector(`input[name="incomeSources[${index}][inss]"]`);
        const irrfInput = row.querySelector(`input[name="incomeSources[${index}][irrf]"]`);
        
        sources.push({
            type: typeSelect ? typeSelect.value : 'salary',
            name: nameInput ? nameInput.value : '',
            gross: grossInput ? parseInputNumber(grossInput.value) : 0,
            inss: inssInput ? parseInputNumber(inssInput.value) : 0,
            irrf: irrfInput ? parseInputNumber(irrfInput.value) : 0,
        });
    });
    
    return sources;
}

function addIncomeSource() {
    const container = document.getElementById('incomeSourcesContainer');
    if (!container) return;
    
    const rows = container.querySelectorAll('.income-source-row');
    const newIndex = rows.length;
    
    const newRow = document.createElement('div');
    newRow.className = 'income-source-row bg-neutral-50 p-4 rounded-lg border border-neutral-200';
    newRow.setAttribute('data-index', newIndex);
    
    newRow.innerHTML = `
        <div class="flex justify-between items-start mb-3">
            <h5 class="text-sm font-semibold text-neutral-800">Fonte de Renda #${newIndex + 1}</h5>
            <button type="button" onclick="removeIncomeSource(${newIndex})" class="remove-income-source-btn p-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-200 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Tipo</label>
                    <select name="incomeSources[${newIndex}][type]" class="form-select w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                        <option value="salary">Salário CLT</option>
                        <option value="prolabore">Pró-Labore</option>
                        <option value="autonomous">Autônomo</option>
                        <option value="retirement">Aposentadoria</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Nome da Fonte</label>
                    <input type="text" name="incomeSources[${newIndex}][name]" placeholder="Ex: Empresa XYZ" class="w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Bruto Anual</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="incomeSources[${newIndex}][gross]" placeholder="0.00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">INSS Retido ou Pago (GPS) <span class="text-neutral-500 font-normal">(anual)</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="incomeSources[${newIndex}][inss]" placeholder="0.00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">IRRF Retido <span class="text-neutral-500 font-normal">(anual)</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="incomeSources[${newIndex}][irrf]" placeholder="0.00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
    updateIncomeRemoveButtons();
    
    // Adicionar event listeners aos novos inputs
    const inputs = newRow.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('input', recalculateSummary);
        input.addEventListener('change', recalculateSummary);
        
        // Adicionar handler para Enter
        if (input.type !== 'checkbox' && input.type !== 'submit' && input.type !== 'button') {
            input.addEventListener('keydown', handleEnterKey);
        }
        
        // Máscaras removidas - campos numéricos sem formatação
    });
    
    recalculateSummary();
}

function removeIncomeSource(index) {
    const container = document.getElementById('incomeSourcesContainer');
    if (!container) return;
    
    const row = container.querySelector(`.income-source-row[data-index="${index}"]`);
    if (!row) return;
    
    const rows = container.querySelectorAll('.income-source-row');
    if (rows.length <= 1) return;
    
    row.remove();
    reindexIncomeSources();
    updateIncomeRemoveButtons();
    recalculateSummary();
}

function reindexIncomeSources() {
    const container = document.getElementById('incomeSourcesContainer');
    if (!container) return;
    
    const remainingRows = container.querySelectorAll('.income-source-row');
    remainingRows.forEach((row, newIndex) => {
        row.setAttribute('data-index', newIndex);
        
        // Atualizar cabeçalho enumerado
        const header = row.querySelector('h5');
        if (header) {
            header.textContent = `Fonte de Renda #${newIndex + 1}`;
        }
        
        const typeSelect = row.querySelector('select[name*="[type]"]');
        const nameInput = row.querySelector('input[name*="[name]"]');
        const grossInput = row.querySelector('input[name*="[gross]"]');
        const inssInput = row.querySelector('input[name*="[inss]"]');
        const irrfInput = row.querySelector('input[name*="[irrf]"]');
        const removeBtn = row.querySelector('.remove-income-source-btn');
        
        if (typeSelect) typeSelect.name = `incomeSources[${newIndex}][type]`;
        if (nameInput) nameInput.name = `incomeSources[${newIndex}][name]`;
        if (grossInput) grossInput.name = `incomeSources[${newIndex}][gross]`;
        if (inssInput) inssInput.name = `incomeSources[${newIndex}][inss]`;
        if (irrfInput) irrfInput.name = `incomeSources[${newIndex}][irrf]`;
        if (removeBtn) {
            removeBtn.setAttribute('onclick', `removeIncomeSource(${newIndex})`);
        }
    });
}

function updateIncomeRemoveButtons() {
    const container = document.getElementById('incomeSourcesContainer');
    if (!container) return;
    
    const rows = container.querySelectorAll('.income-source-row');
    const removeButtons = container.querySelectorAll('.remove-income-source-btn');
    
    removeButtons.forEach(btn => {
        if (rows.length <= 1) {
            btn.classList.add('hidden');
        } else {
            btn.classList.remove('hidden');
        }
    });
}

// ========================================
// REPEATER: IMÓVEIS DE ALUGUEL
// ========================================

function collectRentalProperties() {
    const container = document.getElementById('rentalPropertiesContainer');
    if (!container) return [];
    
    const rows = container.querySelectorAll('.rental-property-row');
    const properties = [];
    
    rows.forEach((row) => {
        const index = row.getAttribute('data-index');
        const nameInput = row.querySelector(`input[name="rentalProperties[${index}][name]"]`);
        const periodicitySelect = row.querySelector(`select[name="rentalProperties[${index}][periodicity]"]`);
        const grossInput = row.querySelector(`input[name="rentalProperties[${index}][gross]"]`);
        const adminFeeInput = row.querySelector(`input[name="rentalProperties[${index}][admin_fee]"]`);
        const iptuInput = row.querySelector(`input[name="rentalProperties[${index}][iptu]"]`);
        const condoInput = row.querySelector(`input[name="rentalProperties[${index}][condo]"]`);
        
        properties.push({
            name: nameInput ? nameInput.value : '',
            periodicity: periodicitySelect ? periodicitySelect.value : 'monthly',
            gross: grossInput ? parseInputNumber(grossInput.value) : 0,
            admin_fee: adminFeeInput ? parseInputNumber(adminFeeInput.value) : 0,
            iptu: iptuInput ? parseInputNumber(iptuInput.value) : 0,
            condo: condoInput ? parseInputNumber(condoInput.value) : 0,
        });
    });
    
    return properties;
}

function addRentalProperty() {
    const container = document.getElementById('rentalPropertiesContainer');
    if (!container) return;
    
    const rows = container.querySelectorAll('.rental-property-row');
    const newIndex = rows.length;
    
    const newRow = document.createElement('div');
    newRow.className = 'rental-property-row bg-neutral-50 p-4 rounded-lg border border-neutral-200';
    newRow.setAttribute('data-index', newIndex);
    
    newRow.innerHTML = `
        <div class="flex justify-between items-start mb-3">
            <h5 class="text-sm font-semibold text-neutral-800">Imóvel #${newIndex + 1}</h5>
            <button type="button" onclick="removeRentalProperty(${newIndex})" class="remove-rental-btn p-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-200 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Identificação do Imóvel</label>
                    <input type="text" name="rentalProperties[${newIndex}][name]" placeholder="Ex: Apt Centro" class="w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Periodicidade</label>
                    <select name="rentalProperties[${newIndex}][periodicity]" class="form-select rental-periodicity-select w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" onchange="updateRentalLabels(${newIndex})">
                        <option value="monthly">Mensais</option>
                        <option value="annual">Anuais</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Aluguel Bruto <span class="rental-label-gross text-neutral-500 font-normal"></span></label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="rentalProperties[${newIndex}][gross]" placeholder="0.00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Taxa Adm. <span class="rental-label-admin text-neutral-500 font-normal"></span></label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="rentalProperties[${newIndex}][admin_fee]" placeholder="0.00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">
                    IPTU
                    <span class="rental-label-iptu block text-neutral-500 font-normal text-xs"></span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="rentalProperties[${newIndex}][iptu]" placeholder="0.00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Condomínio <span class="rental-label-condo text-neutral-500 font-normal"></span></label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="rentalProperties[${newIndex}][condo]" placeholder="0.00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
    updateRentalRemoveButtons();
    
    // Inicializar labels do novo imóvel
    updateRentalLabels(newIndex);
    
    // Adicionar event listeners
    const inputs = newRow.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('input', recalculateSummary);
        input.addEventListener('change', recalculateSummary);
        
        // Adicionar handler para Enter
        if (input.type !== 'checkbox' && input.type !== 'submit' && input.type !== 'button') {
            input.addEventListener('keydown', handleEnterKey);
        }
        
        // Máscaras removidas - campos numéricos sem formatação
    });
    
    recalculateSummary();
}

function removeRentalProperty(index) {
    const container = document.getElementById('rentalPropertiesContainer');
    if (!container) return;
    
    const row = container.querySelector(`.rental-property-row[data-index="${index}"]`);
    if (!row) return;
    
    const rows = container.querySelectorAll('.rental-property-row');
    if (rows.length <= 1) return;
    
    row.remove();
    reindexRentalProperties();
    updateRentalRemoveButtons();
    recalculateSummary();
}

function reindexRentalProperties() {
    const container = document.getElementById('rentalPropertiesContainer');
    if (!container) return;
    
    const remainingRows = container.querySelectorAll('.rental-property-row');
    remainingRows.forEach((row, newIndex) => {
        row.setAttribute('data-index', newIndex);
        
        // Atualizar cabeçalho enumerado
        const header = row.querySelector('h5');
        if (header) {
            header.textContent = `Imóvel #${newIndex + 1}`;
        }
        
        const nameInput = row.querySelector('input[name*="[name]"]');
        const periodicitySelect = row.querySelector('select[name*="[periodicity]"]');
        const grossInput = row.querySelector('input[name*="[gross]"]');
        const adminFeeInput = row.querySelector('input[name*="[admin_fee]"]');
        const iptuInput = row.querySelector('input[name*="[iptu]"]');
        const condoInput = row.querySelector('input[name*="[condo]"]');
        const removeBtn = row.querySelector('.remove-rental-btn');
        
        if (nameInput) nameInput.name = `rentalProperties[${newIndex}][name]`;
        if (periodicitySelect) {
            periodicitySelect.name = `rentalProperties[${newIndex}][periodicity]`;
            periodicitySelect.setAttribute('onchange', `updateRentalLabels(${newIndex})`);
        }
        if (grossInput) grossInput.name = `rentalProperties[${newIndex}][gross]`;
        if (adminFeeInput) adminFeeInput.name = `rentalProperties[${newIndex}][admin_fee]`;
        if (iptuInput) iptuInput.name = `rentalProperties[${newIndex}][iptu]`;
        if (condoInput) condoInput.name = `rentalProperties[${newIndex}][condo]`;
        if (removeBtn) {
            removeBtn.setAttribute('onclick', `removeRentalProperty(${newIndex})`);
        }
    });
}

function updateRentalLabels(index) {
    const row = document.querySelector(`.rental-property-row[data-index="${index}"]`);
    if (!row) return;
    
    const periodicitySelect = row.querySelector(`select[name="rentalProperties[${index}][periodicity]"]`);
    if (!periodicitySelect) return;
    
    const periodicity = periodicitySelect.value;
    const isAnnual = periodicity === 'annual';
    
    // Atualizar labels
    const labelGross = row.querySelector('.rental-label-gross');
    const labelAdmin = row.querySelector('.rental-label-admin');
    const labelIptu = row.querySelector('.rental-label-iptu');
    const labelCondo = row.querySelector('.rental-label-condo');
    
    if (labelGross) {
        labelGross.textContent = isAnnual ? '(total anual)' : '(mensal)';
    }
    if (labelAdmin) {
        labelAdmin.textContent = isAnnual ? '(total anual)' : '(mensal)';
    }
    if (labelIptu) {
        labelIptu.textContent = isAnnual ? '(total anual)' : '(mensal)';
    }
    if (labelCondo) {
        labelCondo.textContent = isAnnual ? '(total anual)' : '(mensal)';
    }
    
    recalculateSummary();
}

function updateRentalRemoveButtons() {
    const container = document.getElementById('rentalPropertiesContainer');
    if (!container) return;
    
    const rows = container.querySelectorAll('.rental-property-row');
    const removeButtons = container.querySelectorAll('.remove-rental-btn');
    
    removeButtons.forEach(btn => {
        if (rows.length <= 1) {
            btn.classList.add('hidden');
        } else {
            btn.classList.remove('hidden');
        }
    });
}

// ========================================
// COLETA DE ESTADO DO FORMULÁRIO
// ========================================

function collectStateFromForm() {
    const numberValue = (id) => {
        const el = document.getElementById(id);
        return el ? parseInputNumber(el.value) : 0;
    };
    const checkboxValue = (id) => {
        const el = document.getElementById(id);
        return el ? el.checked : false;
    };
    const birthDateInput = document.getElementById('birthDate');
    const birthDate = birthDateInput && birthDateInput.value !== '' ? birthDateInput.value : null;

    const incomeSources = collectIncomeSources();
    const rentalProperties = collectRentalProperties();
    
    // Calcular totais das fontes de renda (agora em base anual)
    let totalGrossAnnual = 0;
    let totalInss = 0;
    let totalIrrf = 0;
    
    incomeSources.forEach(source => {
        totalGrossAnnual += source.gross || 0; // Já é anual
        totalInss += source.inss || 0; // Já é anual
        totalIrrf += source.irrf || 0; // Já é anual
    });

    // Calcular totais de aluguéis (considerando periodicidade)
    let totalRentalGross = 0;
    let totalRentalNet = 0;
    
    rentalProperties.forEach(property => {
        const periodicity = property.periodicity || 'monthly';
        const isAnnual = periodicity === 'annual';
        
        // Converter para mensal se necessário
        const grossMonthly = isAnnual ? (property.gross || 0) / 12 : (property.gross || 0);
        const adminFeeMonthly = isAnnual ? (property.admin_fee || 0) / 12 : (property.admin_fee || 0);
        const iptuMonthly = isAnnual ? (property.iptu || 0) / 12 : (property.iptu || 0);
        const condoMonthly = isAnnual ? (property.condo || 0) / 12 : (property.condo || 0);
        
        const deductions = adminFeeMonthly + iptuMonthly + condoMonthly;
        totalRentalGross += grossMonthly;
        totalRentalNet += Math.max(0, grossMonthly - deductions);
    });

    return {
        birthDate: birthDate,
        seriousIllness: checkboxValue('seriousIllness'),
        incomeSources: incomeSources,
        rentalProperties: rentalProperties,
        incomeMonthly: totalGrossAnnual / 12, // Converter para mensal quando necessário
        incomeAnnual: totalGrossAnnual, // Manter valor anual também
        income13: numberValue('income13'),
        // Rendimentos isentos/exclusivos
        dividendsTotal: numberValue('dividendsTotal'),
        dividendsExcess: numberValue('dividendsExcess'),
        jcpTotal: numberValue('jcpTotal'),
        irrfJcpWithheld: numberValue('irrfJcpWithheld'),
        financialInvestments: numberValue('financialInvestments'),
        irrfExclusiveOther: numberValue('irrfExclusiveOther'),
        taxExemptInvestments: numberValue('taxExemptInvestments'),
        fiiDividends: numberValue('fiiDividends'),
        otherExempt: numberValue('otherExempt'),
        // Dados corporativos (Trava IRPFM)
        accountingProfit: numberValue('accountingProfit'),
        distributedProfit: numberValue('distributedProfit'),
        irpjPaid: numberValue('irpjPaid'),
        csllPaid: numberValue('csllPaid'),
        ownershipPercentage: numberValue('ownershipPercentage') || 100,
        // Outros
        incomeOther: totalRentalNet, // Usar aluguel líquido
        taxPaid: numberValue('taxPaid'),
        totalIrrfRetido: totalIrrf,
        totalInssRetido: totalInss,
        totalRentalGross: totalRentalGross,
        totalRentalNet: totalRentalNet,
        dependents: (() => {
            const input = document.getElementById('dependents');
            const val = input && input.value !== '' ? parseInt(input.value, 10) : 0;
            return Number.isFinite(val) && val > 0 ? val : 0;
        })(),
        deductionHealth: numberValue('deductionHealth'),
        deductionEducation: numberValue('deductionEducation'),
        deductionPGBL: numberValue('deductionPGBL'),
    };
}

// ========================================
// CÁLCULO AUTOMÁTICO DE DIVIDENDOS
// ========================================

/**
 * Calcula o valor tributado de dividendos acima do limite anualizado
 * 
 * Conforme Lei 15.270/2025 - Art. 5º e Art. 6º-A:
 * - Limite de isenção: R$ 50.000/mês × 12 = R$ 600.000/ano
 * - Base de cálculo: APENAS o excedente acima de R$ 600.000/ano (incidência marginal)
 * - Alíquota: 10% sobre o excedente
 * - O imposto é RETIDO NA FONTE pela pessoa jurídica
 * 
 * Lógica:
 * - Se totalAnual <= R$ 600.000: excesso = 0 (sem tributação)
 * - Se totalAnual > R$ 600.000: excesso = totalAnual - R$ 600.000
 * - Imposto = excesso × 10%
 * 
 * Exemplo:
 * - Total anual: R$ 720.000
 * - Excesso anual: R$ 720.000 - R$ 600.000 = R$ 120.000
 * - Imposto (10%): R$ 12.000
 * 
 * @param {number} totalAnnual Total anual de dividendos recebidos
 * @returns {number} Valor anual do excesso tributado (base de cálculo)
 */
function calculateDividendsExcess(totalAnnual) {
    if (!totalAnnual || totalAnnual <= 0) return 0;
    
    // Limite anualizado: R$ 50.000/mês × 12 = R$ 600.000/ano
    const ANNUAL_EXEMPTION_LIMIT = 600000;
    
    // Se não exceder o limite, não há excesso tributável
    if (totalAnnual <= ANNUAL_EXEMPTION_LIMIT) return 0;
    
    // Base de cálculo: apenas o excedente (incidência marginal)
    return totalAnnual - ANNUAL_EXEMPTION_LIMIT;
}

/**
 * Atualiza automaticamente o campo dividendsExcess quando dividendsTotal muda
 * E exibe informações sobre a tributação
 */
function updateDividendsExcess() {
    const dividendsTotalInput = document.getElementById('dividendsTotal');
    const dividendsExcessInput = document.getElementById('dividendsExcess');
    const dividendsTaxInfo = document.getElementById('dividendsTaxInfo');
    const dividendsExcessDisplay = document.getElementById('dividendsExcessDisplay');
    const dividendsTaxDisplay = document.getElementById('dividendsTaxDisplay');
    
    if (!dividendsTotalInput || !dividendsExcessInput) return;
    
    // Garantir que o valor está formatado antes de processar
    // A máscara já deve ter formatado, mas vamos garantir
    const rawValue = dividendsTotalInput.value;
    const totalValue = removeCurrencyMask(rawValue);
    const calculatedExcess = calculateDividendsExcess(totalValue);
    const calculatedTax = calculatedExcess * 0.10; // 10% sobre o excesso
    
    // Atualizar campo hidden (sempre enviar valor numérico, mesmo que zero)
    dividendsExcessInput.value = calculatedExcess.toFixed(2);
    
    // Mostrar/ocultar informações e atualizar valores
    if (calculatedExcess > 0 && dividendsTaxInfo && dividendsExcessDisplay && dividendsTaxDisplay) {
        dividendsTaxInfo.classList.remove('hidden');
        dividendsExcessDisplay.textContent = calculatedExcess.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
            style: 'currency',
            currency: 'BRL'
        });
        dividendsTaxDisplay.textContent = calculatedTax.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
            style: 'currency',
            currency: 'BRL'
        });
    } else if (dividendsTaxInfo) {
        dividendsTaxInfo.classList.add('hidden');
    }
    
    // Disparar evento para recalcular métricas
    dividendsExcessInput.dispatchEvent(new Event('input', { bubbles: true }));
}

// ========================================
// CÁLCULO DE MÉTRICAS
// ========================================

function computeIrpfMetrics(state) {
    // Calcular renda bruta total (agora em base anual)
    let totalGrossAnnual = state.incomeAnnual || (state.incomeMonthly ? state.incomeMonthly * 12 : 0);
    
    let grossTaxable = totalGrossAnnual + (state.totalRentalNet * 12);
    
    // Calcular idade a partir da data de nascimento completa
    let age = 0;
    if (state.birthDate) {
        const birthDate = new Date(state.birthDate);
        const today = new Date();
        age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        // Ajustar se ainda não completou aniversário este ano
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
    }

    // Isenção 65+
    if (age >= 65) {
        grossTaxable = Math.max(0, grossTaxable - 24000);
    }

    // Isenção moléstia grave
    if (state.seriousIllness) {
        grossTaxable = state.totalRentalNet * 12;
    }

    const simplifiedDiscount = Math.min(grossTaxable * window.IRPF_CONSTANTS.simplifiedRate, window.IRPF_CONSTANTS.simplifiedCap);
    const baseSimplified = grossTaxable - simplifiedDiscount;
    const taxSimplified = calculateProgressiveTaxIRPF(Math.max(0, baseSimplified));

    let educationWarning = false;
    let educationDeduction = state.deductionEducation;
    if (educationDeduction > window.IRPF_CONSTANTS.educationCap) {
        educationWarning = true;
        educationDeduction = window.IRPF_CONSTANTS.educationCap;
    }

    const pgblCap = Math.max(0, grossTaxable * 0.12);
    const pgblDeduction = Math.min(state.deductionPGBL, pgblCap);

    const totalDeductions = (state.dependents * window.IRPF_CONSTANTS.deductionPerDependent)
        + state.deductionHealth
        + educationDeduction
        + pgblDeduction;

    const baseLegal = Math.max(0, grossTaxable - totalDeductions);
    const taxLegal = calculateProgressiveTaxIRPF(baseLegal);

    // Imposto sobre dividendos (Art. 5º e Art. 6º-A Lei 15.270/2025)
    // 10% sobre o excesso acima de R$ 600.000/ano (limite anualizado: R$ 50k/mês × 12)
    // Base de cálculo: apenas o excedente (incidência marginal)
    const dividendTax = state.dividendsExcess > 0 ? state.dividendsExcess * 0.10 : 0;

    // Imposto sobre JCP: prioriza valor manual, senão calcula 15%
    const jcpTax = (state.irrfJcpWithheld && state.irrfJcpWithheld > 0) 
        ? state.irrfJcpWithheld 
        : ((state.jcpTotal || 0) * 0.15);

    const tax13 = state.income13 > 0 ? calculateMonthlyTaxIRPF(state.income13) : 0;

    const bestTaxOption = Math.min(taxSimplified, taxLegal);
    const isSimplifiedBetter = taxSimplified < taxLegal;
    
    // Incluir IRPFM adicional se disponível
    const irpfmAdditional = parseInputNumber(window.IRPFM_ADDITIONAL || 0);
    const totalTaxLiability = bestTaxOption + dividendTax + tax13 + irpfmAdditional;
    
    // Determinar qual regime vence (IRPFM ou Regime Geral)
    // IRPFM vence quando há adicional de IRPFM (irpfmAdditional > 0)
    const irpfmWins = irpfmAdditional > 0;
    
    // Total imposto pago dedutível no regime geral (sempre dedutível)
    const taxPaidDeductible = (state.taxPaid || 0) 
        + (state.totalIrrfRetido || 0);
    
    // Total imposto pago de tributação exclusiva (só dedutível quando IRPFM vence)
    // Inclui: imposto sobre dividendos, JCP, IRRF sobre outras aplicações
    const taxPaidExclusive = dividendTax 
        + jcpTax 
        + (state.irrfExclusiveOther || 0);
    
    // Conforme Lei 15.270/2025:
    // - Quando IRPFM vence: abate TODOS os impostos (dedutíveis + exclusivos)
    // - Quando Regime Geral vence: abate APENAS impostos dedutíveis (NÃO abate exclusivos)
    const totalTaxPaid = irpfmWins 
        ? (taxPaidDeductible + taxPaidExclusive)  // IRPFM: abate tudo
        : taxPaidDeductible;  // Regime Geral: abate apenas dedutíveis
    
    const finalResult = totalTaxLiability - totalTaxPaid;
    const isNegativeFinalResult = finalResult < 0;
    const displayFinalResult = isNegativeFinalResult ? Math.abs(finalResult) : finalResult;

    // Renda total para IRPFM
    const totalIncome = totalGrossAnnual
        + (state.totalRentalGross * 12)
        + state.income13
        + (state.dividendsTotal || 0)
        + (state.jcpTotal || 0)
        + (state.financialInvestments || 0)
        + (state.taxExemptInvestments || 0)
        + (state.fiiDividends || 0);
        
    const effectiveRate = totalIncome > 0 ? (totalTaxLiability / totalIncome) * 100 : 0;

    let resultLabel = 'SEM SALDO';
    let resultClass = 'text-sm font-bold text-neutral-600 mt-1';
    let cardBorder = 'border-neutral-300';

    if (finalResult > 0) {
        resultLabel = 'IMPOSTO A PAGAR';
        resultClass = 'text-sm font-bold text-red-600 mt-1';
        cardBorder = 'border-red-500';
    } else if (isNegativeFinalResult) {
        resultLabel = 'A RESTITUIR';
        resultClass = 'text-sm font-bold text-green-600 mt-1';
        cardBorder = 'border-green-500';
    }

    let recommendationText = 'Preencha os dados para ver qual regime compensa mais.';
    if (totalIncome > 0) {
        if (Math.abs(taxSimplified - taxLegal) < 10) {
            recommendationText = 'Resultados similares em ambos os regimes.';
        } else if (isSimplifiedBetter) {
            const difference = formatCurrency(taxLegal - taxSimplified);
            recommendationText = '<span class="text-brand-700 font-bold">Recomendação:</span> O Desconto Simplificado economiza <strong>' + difference + '</strong>.';
        } else {
            const difference = formatCurrency(taxSimplified - taxLegal);
            recommendationText = '<span class="text-green-700 font-bold">Recomendação:</span> As Deduções Legais economizam <strong>' + difference + '</strong>.';
        }
    }

    const chartData = {
        simplified: Math.round((taxSimplified + dividendTax) * 100) / 100,
        legal: Math.round((taxLegal + dividendTax) * 100) / 100,
    };

    return {
        grossTaxable,
        simplifiedDiscount,
        totalDeductions,
        dividendTax,
        irpfmAdditional,
        totalTaxLiability,
        taxPaid: state.taxPaid,
        finalResult,
        displayFinalResult,
        effectiveRate,
        resultLabel,
        resultClass,
        cardBorder,
        recommendationText,
        educationWarning,
        chartData,
        isNegativeFinalResult,
        totalIncome,
    };
}

// ========================================
// ATUALIZAÇÃO DA UI
// ========================================

function updateSummaryUI(state, metrics) {
    const setText = (id, text) => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = text;
        }
    };

    setText('displayGrossIncome', formatCurrency(metrics.grossTaxable));
    setText('displaySimplifiedDisc', `- ${formatCurrency(metrics.simplifiedDiscount)}`);
    setText('displayLegalDed', `- ${formatCurrency(metrics.totalDeductions)}`);
    if (metrics.dividendTax > 0) {
        setText('displayDivTax', `+ ${formatCurrency(metrics.dividendTax)}`);
    }
    // Exibir IRPFM se aplicável
    const irpfmEl = document.getElementById('displayIrpfm');
    if (irpfmEl) {
        if (metrics.irpfmAdditional > 0) {
            irpfmEl.textContent = `+ ${formatCurrency(metrics.irpfmAdditional)}`;
            irpfmEl.parentElement.classList.remove('hidden');
        } else {
            irpfmEl.parentElement.classList.add('hidden');
        }
    }
    setText('displayTaxDue', formatCurrency(metrics.totalTaxLiability));
    // Calcular total imposto pago (mesma lógica do computeIrpfMetrics)
    const dividendTax = state.dividendsExcess > 0 ? state.dividendsExcess * 0.10 : 0;
    const jcpTax = (state.irrfJcpWithheld && state.irrfJcpWithheld > 0) 
        ? state.irrfJcpWithheld 
        : ((state.jcpTotal || 0) * 0.15);
    
    // Determinar qual regime vence (IRPFM ou Regime Geral)
    const irpfmWins = metrics.irpfmAdditional > 0;
    
    // Total imposto pago dedutível no regime geral (sempre dedutível)
    const taxPaidDeductible = (state.taxPaid || 0) 
        + (state.totalIrrfRetido || 0);
    
    // Total imposto pago de tributação exclusiva (só dedutível quando IRPFM vence)
    const taxPaidExclusive = dividendTax 
        + jcpTax 
        + (state.irrfExclusiveOther || 0);
    
    // Conforme Lei 15.270/2025:
    // - Quando IRPFM vence: abate TODOS os impostos (dedutíveis + exclusivos)
    // - Quando Regime Geral vence: abate APENAS impostos dedutíveis (NÃO abate exclusivos)
    const totalTaxPaid = irpfmWins 
        ? (taxPaidDeductible + taxPaidExclusive)  // IRPFM: abate tudo
        : taxPaidDeductible;  // Regime Geral: abate apenas dedutíveis
    
    setText('displayTaxPaid', `- ${formatCurrency(totalTaxPaid)}`);

    const finalResultValue = document.getElementById('finalResultValue');
    if (finalResultValue) {
        finalResultValue.textContent = formatCurrency(metrics.displayFinalResult);
    }

    const resultStatus = document.getElementById('resultStatus');
    if (resultStatus) {
        resultStatus.textContent = metrics.resultLabel;
        resultStatus.className = metrics.resultClass;
    }

    const resultCard = document.getElementById('resultCard');
    if (resultCard) {
        resultCard.classList.remove('border-neutral-300', 'border-red-500', 'border-green-500');
        resultCard.classList.add(metrics.cardBorder);
    }

    const effectiveRateEl = document.getElementById('effectiveRate');
    if (effectiveRateEl) {
        effectiveRateEl.textContent = `${metrics.effectiveRate.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
    }

    const recommendationTextEl = document.getElementById('recommendationText');
    if (recommendationTextEl) {
        recommendationTextEl.innerHTML = metrics.recommendationText;
    }

    const educationWarningEl = document.getElementById('educationWarning');
    if (educationWarningEl) {
        educationWarningEl.classList.toggle('hidden', !metrics.educationWarning);
    }
}

function updateChartData(metrics) {
    if (window.chartPayload) {
        window.chartPayload.simplified = metrics.chartData.simplified;
        window.chartPayload.legal = metrics.chartData.legal;

        if (comparisonChart) {
            comparisonChart.data.datasets[0].data = [window.chartPayload.simplified, window.chartPayload.legal];
            comparisonChart.update();
        }
    }
}

function recalculateSummary() {
    const state = collectStateFromForm();
    const metrics = computeIrpfMetrics(state);
    updateSummaryUI(state, metrics);
    updateChartData(metrics);
}

// ========================================
// NAVEGAÇÃO E CONTROLES
// ========================================

function nextStep(stepNumber) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    const target = document.getElementById(`step${stepNumber}`);
    if (target) {
        target.classList.add('active');
    }
    const progress = Math.min(100, Math.max(20, stepNumber * 20));
    const bar = document.getElementById('progressBar');
    if (bar) {
        bar.style.width = `${progress}%`;
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function adjustCounter(id, change) {
    const input = document.getElementById(id);
    if (!input) return;
    let val = parseInt(input.value || '0', 10) + change;
    if (val < 0) val = 0;
    input.value = val;
    recalculateSummary();
}

function toggleDetails() {
    const content = document.getElementById('detailsContent');
    const arrow = document.getElementById('detailsArrow');
    
    if (content) content.classList.toggle('hidden');
    if (arrow) arrow.classList.toggle('rotate-180');
}

function showProductTab(tabName) {
    // Esconder todos os conteúdos
    document.querySelectorAll('.product-content').forEach(el => el.classList.add('hidden'));
    
    // Remover estilo ativo de todas as abas
    document.querySelectorAll('.product-tab').forEach(el => {
        el.classList.remove('text-brand-700', 'bg-brand-50', 'border-b-2', 'border-brand-600');
        el.classList.add('text-neutral-500');
    });
    
    // Mostrar conteúdo selecionado
    const content = document.getElementById('content-' + tabName);
    if (content) content.classList.remove('hidden');
    
    // Ativar aba selecionada
    const activeTab = document.getElementById('tab-' + tabName);
    if (activeTab) {
        activeTab.classList.remove('text-neutral-500');
        activeTab.classList.add('text-brand-700', 'bg-brand-50', 'border-b-2', 'border-brand-600');
    }
}

function resetApp() {
    if (confirm('Deseja reiniciar o simulador?')) {
        window.location = window.location.pathname;
    }
}

// ========================================
// GRÁFICOS
// ========================================

function initCharts() {
    // Gráfico de comparação de regimes
    const comparisonCtx = document.getElementById('comparisonChart');
    if (comparisonCtx && window.chartPayload) {
        comparisonChart = new Chart(comparisonCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Simplificado', 'Completo'],
                datasets: [{
                    label: 'Imposto Anual',
                    data: [window.chartPayload.simplified, window.chartPayload.legal],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
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
                                return 'R$ ' + (value / 1000).toFixed(0) + 'k';
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

    // Gráfico de Holding (se existir)
    const holdingCtx = document.getElementById('holdingChart');
    if (holdingCtx && window.holdingData) {
        holdingChart = new Chart(holdingCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['PF', 'Holding'],
                datasets: [{
                    label: 'Imposto Mensal',
                    data: [window.holdingData.pf || 0, window.holdingData.pj || 0],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderRadius: 8
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
                    x: { beginAtZero: true, grid: { display: false } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
}

// ========================================
// NAVEGAÇÃO COM ENTER
// ========================================

function handleEnterKey(event) {
    // Se não for Enter, não fazer nada
    if (event.key !== 'Enter') {
        return;
    }
    
    // Prevenir submit padrão do formulário
    event.preventDefault();
    event.stopPropagation();
    
    const currentInput = event.target;
    const form = currentInput.closest('form');
    if (!form) return;
    
    // Obter todos os campos focáveis na etapa atual
    const currentStep = currentInput.closest('.step-content');
    if (!currentStep) return;
    
    // Coletar todos os campos focáveis na etapa atual (apenas inputs, selects e textareas)
    const focusableElements = currentStep.querySelectorAll(
        'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([readonly]):not([disabled]), ' +
        'select:not([disabled]), ' +
        'textarea:not([readonly]):not([disabled])'
    );
    
    // Converter NodeList para Array para facilitar manipulação
    const focusableArray = Array.from(focusableElements);
    
    // Encontrar o índice do campo atual
    const currentIndex = focusableArray.indexOf(currentInput);
    
    if (currentIndex === -1) return;
    
    // Tentar encontrar o próximo campo
    let nextField = null;
    
    // Procurar próximo campo focável após o atual
    for (let i = currentIndex + 1; i < focusableArray.length; i++) {
        const element = focusableArray[i];
        // Verificar se o elemento está visível
        if (element.offsetParent !== null) {
            nextField = element;
            break;
        }
    }
    
    // Se encontrou próximo campo, focar nele
    if (nextField) {
        nextField.focus();
        // Se for um input de texto ou número, selecionar o conteúdo
        if (nextField.tagName === 'INPUT' && (nextField.type === 'text' || nextField.type === 'number')) {
            nextField.select();
        }
        return;
    }
    
    // Se não há próximo campo na etapa atual, verificar se há próxima etapa
    const currentStepId = currentStep.id;
    const stepNumber = parseInt(currentStepId.replace('step', ''));
    
    if (stepNumber < 5) {
        // Avançar para próxima etapa
        nextStep(stepNumber + 1);
        
        // Focar no primeiro campo da próxima etapa após um pequeno delay
        setTimeout(() => {
            const nextStepElement = document.getElementById(`step${stepNumber + 1}`);
            if (nextStepElement) {
                const firstField = nextStepElement.querySelector(
                    'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([readonly]):not([disabled]), ' +
                    'select:not([disabled]), ' +
                    'textarea:not([readonly]):not([disabled])'
                );
                if (firstField && firstField.offsetParent !== null) {
                    firstField.focus();
                    if (firstField.tagName === 'INPUT' && (firstField.type === 'text' || firstField.type === 'number')) {
                        firstField.select();
                    }
                }
            }
        }, 150);
    } else {
        // Última etapa - se for o último campo, submeter o formulário
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.click();
        }
    }
}

// ========================================
// INICIALIZAÇÃO
// ========================================

function setupRealtimeCalculation() {
    const inputs = document.querySelectorAll('input[type="number"], input[type="checkbox"], input[type="text"], select, textarea');
    inputs.forEach(input => {
        const eventName = input.type === 'checkbox' ? 'change' : 'input';
        input.addEventListener(eventName, recalculateSummary);
        
        // Adicionar handler para Enter em campos de input, select e textarea
        // Usar capture phase para garantir que seja executado antes de outros handlers
        if (input.type !== 'checkbox' && input.type !== 'submit' && input.type !== 'button') {
            input.addEventListener('keydown', handleEnterKey, true);
        }
        
        // Máscaras removidas - campos numéricos sem formatação
    });
    
    // Adicionar listener específico para cálculo automático de dividendos
    const dividendsTotalInput = document.getElementById('dividendsTotal');
    if (dividendsTotalInput) {
        dividendsTotalInput.addEventListener('input', function(e) {
            updateDividendsExcess();
            recalculateSummary();
        });
    }
    
    updateIncomeRemoveButtons();
    updateRentalRemoveButtons();
    
    // Inicializar labels de periodicidade para todos os imóveis existentes
    const rentalRows = document.querySelectorAll('.rental-property-row');
    rentalRows.forEach(row => {
        const index = row.getAttribute('data-index');
        if (index !== null) {
            updateRentalLabels(parseInt(index, 10));
        }
    });
    
    // Calcular dividendsExcess inicial se houver valor em dividendsTotal
    updateDividendsExcess();
    
    recalculateSummary();
}

function showFinalResult() {
    const resultCard = document.getElementById('resultCard');
    if (!resultCard) return;
    resultCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    resultCard.classList.add('ring-4', 'ring-brand-300', 'transform', 'scale-105');
    setTimeout(() => {
        resultCard.classList.remove('ring-4', 'ring-brand-300', 'transform', 'scale-105');
    }, 1000);
}

// Variável global para controlar se o submit é permitido
window.allowFormSubmit = false;

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    setupRealtimeCalculation();
    
    // Interceptar submit do formulário - só permitir se for explicitamente do botão de submit
    const form = document.getElementById('taxForm');
    if (form) {
        // Marcar quando o botão de submit é clicado
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.addEventListener('click', (e) => {
                window.allowFormSubmit = true;
            });
        }
        
        // Prevenir submit se não foi explicitamente permitido
        form.addEventListener('submit', (event) => {
            if (!window.allowFormSubmit) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                return false;
            }
            // Resetar flag após permitir submit
            window.allowFormSubmit = false;
        }, true); // Usar capture phase para interceptar antes
        
        // Garantir que a flag está false no início
        window.allowFormSubmit = false;
    }
    
    if (window.hasSubmission) {
        showFinalResult();
    }
});

// ========================================
// EXPOR FUNÇÕES GLOBALMENTE
// ========================================
// Necessário para os onclick do HTML funcionarem com Vite

window.nextStep = nextStep;
window.adjustCounter = adjustCounter;
window.toggleDetails = toggleDetails;
window.showProductTab = showProductTab;
window.resetApp = resetApp;
window.addIncomeSource = addIncomeSource;
window.removeIncomeSource = removeIncomeSource;
window.addRentalProperty = addRentalProperty;
window.removeRentalProperty = removeRentalProperty;
window.updateRentalLabels = updateRentalLabels;
