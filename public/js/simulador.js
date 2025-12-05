// ========================================
// SISTEMA DE INTELIGÊNCIA TRIBUTÁRIA
// Lei 15.270/2025 (IRPF 2026)
// ========================================

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
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
    }
    const normalized = String(value).replace(/\./g, '').replace(',', '.');
    const parsed = parseFloat(normalized);
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
                    <input type="number" step="0.01" name="incomeSources[${newIndex}][gross]" placeholder="0,00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">INSS Retido ou Pago (GPS) <span class="text-neutral-500 font-normal">(anual)</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="incomeSources[${newIndex}][inss]" placeholder="0,00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">IRRF Retido <span class="text-neutral-500 font-normal">(anual)</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="incomeSources[${newIndex}][irrf]" placeholder="0,00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
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
        const grossInput = row.querySelector(`input[name="rentalProperties[${index}][gross]"]`);
        const adminFeeInput = row.querySelector(`input[name="rentalProperties[${index}][admin_fee]"]`);
        const iptuInput = row.querySelector(`input[name="rentalProperties[${index}][iptu]"]`);
        const condoInput = row.querySelector(`input[name="rentalProperties[${index}][condo]"]`);
        
        properties.push({
            name: nameInput ? nameInput.value : '',
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
                    <input type="number" step="0.01" name="rentalProperties[${newIndex}][gross]" placeholder="0,00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Taxa Adm. <span class="rental-label-admin text-neutral-500 font-normal"></span></label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="rentalProperties[${newIndex}][admin_fee]" placeholder="0,00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">
                    IPTU
                    <span class="rental-label-iptu block text-neutral-500 font-normal text-xs"></span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="rentalProperties[${newIndex}][iptu]" placeholder="0,00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Condomínio <span class="rental-label-condo text-neutral-500 font-normal"></span></label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400 text-sm">R$</span>
                    <input type="number" step="0.01" name="rentalProperties[${newIndex}][condo]" placeholder="0,00" class="pl-10 w-full p-3 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
    updateRentalRemoveButtons();
    
    // Adicionar event listeners
    const inputs = newRow.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', recalculateSummary);
        
        // Adicionar handler para Enter
        if (input.type !== 'checkbox' && input.type !== 'submit' && input.type !== 'button') {
            input.addEventListener('keydown', handleEnterKey);
        }
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

    // Calcular totais de aluguéis
    let totalRentalGross = 0;
    let totalRentalNet = 0;
    
    rentalProperties.forEach(property => {
        const gross = property.gross || 0;
        const deductions = (property.admin_fee || 0) + (property.iptu || 0) + (property.condo || 0);
        totalRentalGross += gross;
        totalRentalNet += Math.max(0, gross - deductions);
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

    // Imposto sobre dividendos (Art. 5º Lei 15.270)
    const dividendTax = state.dividendsExcess > 0 ? state.dividendsExcess * 0.10 : 0;

    // Imposto sobre JCP: prioriza valor manual, senão calcula 15%
    const jcpTax = (state.irrfJcpWithheld && state.irrfJcpWithheld > 0) 
        ? state.irrfJcpWithheld 
        : ((state.jcpTotal || 0) * 0.15);

    const tax13 = state.income13 > 0 ? calculateMonthlyTaxIRPF(state.income13) : 0;

    const bestTaxOption = Math.min(taxSimplified, taxLegal);
    const isSimplifiedBetter = taxSimplified < taxLegal;
    const totalTaxLiability = bestTaxOption + dividendTax + tax13;
    
    // Total imposto pago: IRRF retido + imposto dividendos + JCP (prioritário manual) + IRRF outras aplicações + carnê-leão
    const totalTaxPaid = (state.taxPaid || 0) 
        + (state.totalIrrfRetido || 0) 
        + dividendTax 
        + jcpTax 
        + (state.irrfExclusiveOther || 0);
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
    setText('displayDivTax', `+ ${formatCurrency(metrics.dividendTax)}`);
    setText('displayTaxDue', formatCurrency(metrics.totalTaxLiability));
    // Calcular total imposto pago (mesma lógica do computeIrpfMetrics)
    const dividendTax = state.dividendsExcess > 0 ? state.dividendsExcess * 0.10 : 0;
    const jcpTax = (state.irrfJcpWithheld && state.irrfJcpWithheld > 0) 
        ? state.irrfJcpWithheld 
        : ((state.jcpTotal || 0) * 0.15);
    const totalTaxPaid = (state.taxPaid || 0) 
        + (state.totalIrrfRetido || 0) 
        + dividendTax 
        + jcpTax 
        + (state.irrfExclusiveOther || 0);
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
    });
    
    updateIncomeRemoveButtons();
    updateRentalRemoveButtons();
    
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
