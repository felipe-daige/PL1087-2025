@php
    $taxCalculatorService = app(\App\Services\TaxCalculatorService::class);
    $irpfFieldValue = function($key, $stateValue) use ($request, $submitted, $taxCalculatorService) {
        return $taxCalculatorService->irpfFieldValue($request, $key, $stateValue, $submitted);
    };
    
    // Fontes de renda
    $incomeSources = $request->input('incomeSources', []);
    if (empty($incomeSources) && isset($state['incomeSources']) && !empty($state['incomeSources'])) {
        $incomeSources = $state['incomeSources'];
    }
    if (empty($incomeSources)) {
        $incomeSources = [['name' => '', 'gross' => '', 'inss' => '', 'irrf' => '', 'type' => 'salary']];
    }
    
    // Imóveis de aluguel
    $rentalProperties = $request->input('rentalProperties', []);
    if (empty($rentalProperties) && isset($state['rentalProperties']) && !empty($state['rentalProperties'])) {
        $rentalProperties = $state['rentalProperties'];
    }
    if (empty($rentalProperties)) {
        $rentalProperties = [['name' => '', 'gross' => '', 'admin_fee' => '', 'iptu' => '', 'condo' => '']];
    }
    
    // Verifica se é alta renda (para mostrar seção PJ)
    $isHighIncome = ($state['highIncomeTrigger'] ?? false) || 
                    (($state['incomeMonthly'] ?? 0) * 12 + 
                     ($state['dividendsTotal'] ?? 0) + 
                     ($state['jcpTotal'] ?? 0)) > 600000;
@endphp

<form method="POST" action="{{ route('simulador.store') }}" class="flex flex-col gap-6" id="taxForm">
    @csrf
    
    {{-- Barra de Progresso --}}
    <div class="w-full bg-neutral-200 rounded-full h-2.5 mb-2">
        <div id="progressBar" class="bg-brand-600 h-2.5 rounded-full transition-all duration-500" style="width: 20%"></div>
    </div>

    {{-- ========================================
         ETAPA 1: PERFIL E PRIORIDADES
         ======================================== --}}
    <div id="step1" class="step-content active bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
        <div class="mb-6">
            <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Etapa 1 de 5</span>
            <h3 class="text-2xl font-bold text-neutral-800 mt-1">Perfil e Prioridades</h3>
            <p class="text-neutral-500 text-sm mt-2">Verificamos isenções prioritárias antes de calcular a renda.</p>
        </div>
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2" for="birthYear">Ano de Nascimento</label>
                <input type="number" id="birthYear" name="birthYear" placeholder="Ex: 1980" 
                       value="{{ e($irpfFieldValue('birthYear', $state['birthYear'] ?? '')) }}" 
                       class="w-full p-3 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-shadow outline-none">
                <p class="text-xs text-neutral-400 mt-1">Usado para isenção extra de 65+ anos (R$ 24.000/ano).</p>
            </div>
            <div class="flex items-start p-4 bg-neutral-50 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-100 transition-colors">
                <div class="flex items-center h-5">
                    <input id="seriousIllness" name="seriousIllness" type="checkbox" 
                           class="w-4 h-4 text-brand-600 border-neutral-300 rounded focus:ring-brand-500" 
                           {{ ($state['seriousIllness'] ?? false) ? 'checked' : '' }}>
                </div>
                <div class="ml-3 text-sm">
                    <label for="seriousIllness" class="font-medium text-neutral-700">Possui moléstia grave prevista em lei?</label>
                    <p class="text-neutral-500 text-xs mt-1">Ex: Cardiopatia grave, alienação mental, neoplasia maligna. Isenta rendimentos de aposentadoria.</p>
                </div>
            </div>
        </div>
        <div class="mt-8 flex justify-end">
            <button type="button" onclick="nextStep(2)" class="px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm flex items-center">
                Próximo: Rendimentos <span class="ml-2">→</span>
            </button>
        </div>
    </div>

    {{-- ========================================
         ETAPA 2: RENDIMENTOS TRIBUTÁVEIS
         ======================================== --}}
    <div id="step2" class="step-content bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
        <div class="mb-6">
            <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Etapa 2 de 5</span>
            <h3 class="text-2xl font-bold text-neutral-800 mt-1">Rendimentos Tributáveis</h3>
            <p class="text-neutral-500 text-sm mt-2">Lei 15.270/2025: Isenção até R$ 5.000/mês. Informe suas fontes de renda.</p>
        </div>
        <div class="space-y-6">
            {{-- Fontes de Renda --}}
            <div class="p-4 border border-neutral-200 rounded-lg">
                <h4 class="font-semibold text-neutral-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Salários, Pró-Labore e Aposentadoria
                </h4>
                <p class="text-xs text-neutral-500 mb-4">Adicione cada fonte separadamente para calcular o teto do INSS globalmente.</p>
                
                <div id="incomeSourcesContainer" class="space-y-4">
                    @foreach($incomeSources as $index => $source)
                    <div class="income-source-row bg-neutral-50 p-4 rounded-lg border border-neutral-200" data-index="{{ $index }}">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-neutral-700 mb-1">Tipo</label>
                                <select name="incomeSources[{{ $index }}][type]" class="form-select w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                                    <option value="salary" {{ ($source['type'] ?? 'salary') === 'salary' ? 'selected' : '' }}>Salário CLT</option>
                                    <option value="prolabore" {{ ($source['type'] ?? '') === 'prolabore' ? 'selected' : '' }}>Pró-Labore</option>
                                    <option value="autonomous" {{ ($source['type'] ?? '') === 'autonomous' ? 'selected' : '' }}>Autônomo</option>
                                    <option value="retirement" {{ ($source['type'] ?? '') === 'retirement' ? 'selected' : '' }}>Aposentadoria</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-neutral-700 mb-1">Nome da Fonte</label>
                                <input type="text" name="incomeSources[{{ $index }}][name]" value="{{ e($source['name'] ?? '') }}" placeholder="Ex: Empresa XYZ" class="w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-neutral-700 mb-1">Bruto Mensal</label>
                                <div class="relative">
                                    <span class="absolute left-2 top-2 text-neutral-400 text-xs">R$</span>
                                    <input type="number" step="0.01" name="incomeSources[{{ $index }}][gross]" value="{{ e($source['gross'] ?? '') }}" placeholder="0,00" class="pl-8 w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-neutral-700 mb-1">INSS Retido</label>
                                <div class="relative">
                                    <span class="absolute left-2 top-2 text-neutral-400 text-xs">R$</span>
                                    <input type="number" step="0.01" name="incomeSources[{{ $index }}][inss]" value="{{ e($source['inss'] ?? '') }}" placeholder="0,00" class="pl-8 w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-neutral-700 mb-1">IRRF Retido</label>
                                <div class="relative">
                                    <span class="absolute left-2 top-2 text-neutral-400 text-xs">R$</span>
                                    <input type="number" step="0.01" name="incomeSources[{{ $index }}][irrf]" value="{{ e($source['irrf'] ?? '') }}" placeholder="0,00" class="pl-8 w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none income-source-input">
                                </div>
                            </div>
                            <div class="md:col-span-1">
                                <button type="button" onclick="removeIncomeSource({{ $index }})" class="remove-income-source-btn w-full p-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-200 rounded-lg transition-colors {{ count($incomeSources) <= 1 ? 'hidden' : '' }}">
                                    <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <button type="button" onclick="addIncomeSource()" class="mt-4 px-4 py-2 bg-brand-50 text-brand-700 text-sm font-medium rounded-lg hover:bg-brand-100 transition-colors border border-brand-200 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Adicionar Fonte
                </button>
            </div>

            {{-- 13º Salário --}}
            <div class="p-4 border border-neutral-200 rounded-lg">
                <label class="block text-sm font-medium text-neutral-700 mb-1" for="income13">13º Salário (Total Anual)</label>
                <div class="relative max-w-xs">
                    <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                    <input type="number" step="0.01" id="income13" name="income13" 
                           class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                           placeholder="0,00" value="{{ e($irpfFieldValue('income13', $state['income13'] ?? '')) }}">
                </div>
                <p class="text-xs text-neutral-400 mt-1">Tributação exclusiva na fonte (não soma à base mensal).</p>
            </div>
        </div>
        <div class="mt-8 flex justify-between">
            <button type="button" onclick="nextStep(1)" class="px-6 py-3 text-neutral-600 font-medium hover:text-neutral-900 transition-colors">← Voltar</button>
            <button type="button" onclick="nextStep(3)" class="px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                Próximo: Imóveis →
            </button>
        </div>
    </div>

    {{-- ========================================
         ETAPA 3: IMÓVEIS E ALUGUÉIS
         ======================================== --}}
    <div id="step3" class="step-content bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
        <div class="mb-6">
            <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Etapa 3 de 5</span>
            <h3 class="text-2xl font-bold text-neutral-800 mt-1">Imóveis e Aluguéis</h3>
            <p class="text-neutral-500 text-sm mt-2">Informe seus imóveis para comparar tributação PF vs. Holding Patrimonial.</p>
        </div>
        <div class="space-y-6">
            <div class="p-4 border border-neutral-200 rounded-lg">
                <h4 class="font-semibold text-neutral-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Imóveis de Aluguel
                </h4>
                <p class="text-xs text-neutral-500 mb-4">Taxa de administração, IPTU e condomínio são dedutíveis quando pagos pelo proprietário.</p>
                
                <div id="rentalPropertiesContainer" class="space-y-4">
                    @foreach($rentalProperties as $index => $property)
                    <div class="rental-property-row bg-neutral-50 p-4 rounded-lg border border-neutral-200" data-index="{{ $index }}">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-neutral-700 mb-1">Identificação do Imóvel</label>
                                <input type="text" name="rentalProperties[{{ $index }}][name]" value="{{ e($property['name'] ?? '') }}" placeholder="Ex: Apt Centro" class="w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-xs font-medium text-neutral-700 mb-1">Aluguel Bruto</label>
                                <div class="relative">
                                    <span class="absolute left-2 top-2 text-neutral-400 text-xs">R$</span>
                                    <input type="number" step="0.01" name="rentalProperties[{{ $index }}][gross]" value="{{ e($property['gross'] ?? '') }}" placeholder="0,00" class="pl-8 w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                                </div>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-xs font-medium text-neutral-700 mb-1">Taxa Adm.</label>
                                <div class="relative">
                                    <span class="absolute left-2 top-2 text-neutral-400 text-xs">R$</span>
                                    <input type="number" step="0.01" name="rentalProperties[{{ $index }}][admin_fee]" value="{{ e($property['admin_fee'] ?? '') }}" placeholder="0,00" class="pl-8 w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                                </div>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-xs font-medium text-neutral-700 mb-1">IPTU/mês</label>
                                <div class="relative">
                                    <span class="absolute left-2 top-2 text-neutral-400 text-xs">R$</span>
                                    <input type="number" step="0.01" name="rentalProperties[{{ $index }}][iptu]" value="{{ e($property['iptu'] ?? '') }}" placeholder="0,00" class="pl-8 w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                                </div>
                            </div>
                            <div class="md:col-span-1 flex gap-2">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-neutral-700 mb-1">Condomínio</label>
                                    <div class="relative">
                                        <span class="absolute left-2 top-2 text-neutral-400 text-xs">R$</span>
                                        <input type="number" step="0.01" name="rentalProperties[{{ $index }}][condo]" value="{{ e($property['condo'] ?? '') }}" placeholder="0,00" class="pl-8 w-full p-2 text-sm border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none">
                                    </div>
                                </div>
                                <button type="button" onclick="removeRentalProperty({{ $index }})" class="remove-rental-btn self-end p-2 text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-200 rounded-lg transition-colors {{ count($rentalProperties) <= 1 ? 'hidden' : '' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <button type="button" onclick="addRentalProperty()" class="mt-4 px-4 py-2 bg-brand-50 text-brand-700 text-sm font-medium rounded-lg hover:bg-brand-100 transition-colors border border-brand-200 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Adicionar Imóvel
                </button>
            </div>

            {{-- Imposto já pago (Carnê-Leão) --}}
            <div class="p-4 border border-neutral-200 rounded-lg bg-amber-50/50">
                <label class="block text-sm font-medium text-neutral-700 mb-1" for="taxPaid">Carnê-Leão Pago no Ano</label>
                <div class="relative max-w-xs">
                    <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                    <input type="number" step="0.01" id="taxPaid" name="taxPaid" 
                           class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                           placeholder="Total pago no ano" value="{{ e($irpfFieldValue('taxPaid', $state['taxPaid'] ?? '')) }}">
                </div>
                <p class="text-xs text-neutral-400 mt-1">Imposto antecipado sobre aluguéis e exterior (será deduzido do saldo final).</p>
            </div>
        </div>
        <div class="mt-8 flex justify-between">
            <button type="button" onclick="nextStep(2)" class="px-6 py-3 text-neutral-600 font-medium hover:text-neutral-900 transition-colors">← Voltar</button>
            <button type="button" onclick="nextStep(4)" class="px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                Próximo: Deduções →
            </button>
        </div>
    </div>

    {{-- ========================================
         ETAPA 4: DEDUÇÕES
         ======================================== --}}
    <div id="step4" class="step-content bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
        <div class="mb-6">
            <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Etapa 4 de 5</span>
            <h3 class="text-2xl font-bold text-neutral-800 mt-1">Otimização Fiscal</h3>
            <p class="text-neutral-500 text-sm mt-2">Informe seus gastos dedutíveis para comparar com o desconto simplificado.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Dependentes --}}
            <div class="col-span-1 md:col-span-2 bg-neutral-50 p-4 rounded-lg">
                <label class="block text-sm font-medium text-neutral-700 mb-2">Número de Dependentes</label>
                <div class="flex items-center gap-4">
                    <button type="button" onclick="adjustCounter('dependents', -1)" class="w-10 h-10 rounded-full bg-white border border-neutral-300 flex items-center justify-center text-neutral-600 hover:bg-neutral-100 font-bold text-xl">−</button>
                    <input type="number" id="dependents" name="dependents" value="{{ e($request->input('dependents', $state['dependents'] ?? 0)) }}" readonly class="w-16 text-center bg-transparent font-bold text-xl text-brand-700">
                    <button type="button" onclick="adjustCounter('dependents', 1)" class="w-10 h-10 rounded-full bg-white border border-neutral-300 flex items-center justify-center text-neutral-600 hover:bg-neutral-100 font-bold text-xl">+</button>
                </div>
                <p class="text-xs text-neutral-400 mt-2">Dedução: R$ 2.275,08/ano por dependente.</p>
            </div>

            {{-- Saúde --}}
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1" for="deductionHealth">
                    Gastos com Saúde
                    <span class="text-green-600 text-xs font-normal">(sem limite)</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                    <input type="number" step="0.01" id="deductionHealth" name="deductionHealth" 
                           class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                           placeholder="0,00" value="{{ e($irpfFieldValue('deductionHealth', $state['deductionHealth'] ?? '')) }}">
                </div>
            </div>

            {{-- Educação --}}
            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1" for="deductionEducation">
                    Gastos com Educação
                    <span class="text-amber-600 text-xs font-normal">(limite R$ 3.561,50/pessoa)</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                    <input type="number" step="0.01" id="deductionEducation" name="deductionEducation" 
                           class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                           placeholder="0,00" value="{{ e($irpfFieldValue('deductionEducation', $state['deductionEducation'] ?? '')) }}">
                </div>
                <p id="educationWarning" class="text-xs text-orange-600 mt-1 {{ ($educationWarning ?? false) ? '' : 'hidden' }}">Limite aplicado: R$ {{ number_format($constants['educationCap'] ?? 3561.50, 2, ',', '.') }}.</p>
            </div>

            {{-- PGBL --}}
            <div class="col-span-1 md:col-span-2">
                <label class="block text-sm font-medium text-neutral-700 mb-1" for="deductionPGBL">
                    Previdência Privada (PGBL)
                    <span class="text-amber-600 text-xs font-normal">(limite 12% da renda tributável)</span>
                </label>
                <div class="relative max-w-md">
                    <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                    <input type="number" step="0.01" id="deductionPGBL" name="deductionPGBL" 
                           class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                           placeholder="0,00" value="{{ e($irpfFieldValue('deductionPGBL', $state['deductionPGBL'] ?? '')) }}">
                </div>
            </div>
        </div>
        <div class="mt-8 flex justify-between">
            <button type="button" onclick="nextStep(3)" class="px-6 py-3 text-neutral-600 font-medium hover:text-neutral-900 transition-colors">← Voltar</button>
            <button type="button" onclick="nextStep(5)" class="px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                Próximo: Rendimentos Isentos →
            </button>
        </div>
    </div>

    {{-- ========================================
         ETAPA 5: RENDIMENTOS ISENTOS E DADOS PJ
         ======================================== --}}
    <div id="step5" class="step-content bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
        <div class="mb-6">
            <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Etapa 5 de 5</span>
            <h3 class="text-2xl font-bold text-neutral-800 mt-1">Imposto Mínimo (IRPFM)</h3>
            <p class="text-neutral-500 text-sm mt-2">Lei 15.270/2025: Renda total > R$ 600k/ano ativa o imposto mínimo. Informe rendimentos isentos.</p>
        </div>
        <div class="space-y-6">
            {{-- Dividendos --}}
            <div class="p-4 border border-brand-200 rounded-lg bg-brand-50/40">
                <h4 class="font-semibold text-neutral-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    Lucros e Dividendos
                    <span class="text-xs font-normal text-brand-600">(Art. 5º Lei 15.270)</span>
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="dividendsTotal">Total Recebido no Ano</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="dividendsTotal" name="dividendsTotal" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('dividendsTotal', $state['dividendsTotal'] ?? '')) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="dividendsExcess">
                            Valor Tributado (> R$ 50k/mês)
                            <span class="text-red-500 text-xs">10% na fonte</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="dividendsExcess" name="dividendsExcess" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('dividendsExcess', $state['dividendsExcess'] ?? '')) }}">
                        </div>
                        <p class="text-xs text-brand-600 mt-1">Soma dos valores que excederam R$ 50.000/mês por fonte.</p>
                    </div>
                </div>
            </div>

            {{-- JCP e Aplicações --}}
            <div class="p-4 border border-neutral-200 rounded-lg">
                <h4 class="font-semibold text-neutral-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Outros Rendimentos
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="jcpTotal">
                            JCP Recebido
                            <span class="text-neutral-400 text-xs">(15% retido)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="jcpTotal" name="jcpTotal" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('jcpTotal', $state['jcpTotal'] ?? '')) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="financialInvestments">Aplicações Financeiras</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="financialInvestments" name="financialInvestments" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('financialInvestments', $state['financialInvestments'] ?? '')) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="taxExemptInvestments">
                            LCI/LCA/CRI/CRA
                            <span class="text-green-600 text-xs">(isentos)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="taxExemptInvestments" name="taxExemptInvestments" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('taxExemptInvestments', $state['taxExemptInvestments'] ?? '')) }}">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="fiiDividends">
                            Dividendos de FIIs
                            <span class="text-green-600 text-xs">(isentos PF)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="fiiDividends" name="fiiDividends" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('fiiDividends', $state['fiiDividends'] ?? '')) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="otherExempt">Outros Isentos</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="otherExempt" name="otherExempt" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('otherExempt', $state['otherExempt'] ?? '')) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dados Corporativos (Trava IRPFM) --}}
            <div class="p-4 border-2 border-amber-300 rounded-lg bg-amber-50/50" id="corporateDataSection">
                <h4 class="font-semibold text-amber-900 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Trava Anti-Bitributação (Art. 4º, §3º)
                </h4>
                <p class="text-xs text-amber-800 mb-4">Se você é sócio de PJ, o IRPJ/CSLL pago pela empresa pode ser abatido do IRPFM. Preencha para ativar o crédito.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="accountingProfit">Lucro Contábil da Empresa</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="accountingProfit" name="accountingProfit" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('accountingProfit', $state['accountingProfit'] ?? '')) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="distributedProfit">Lucro Distribuído a Você</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="distributedProfit" name="distributedProfit" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('distributedProfit', $state['distributedProfit'] ?? '')) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="irpjPaid">IRPJ Pago pela Empresa</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="irpjPaid" name="irpjPaid" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('irpjPaid', $state['irpjPaid'] ?? '')) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="csllPaid">CSLL Paga pela Empresa</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                            <input type="number" step="0.01" id="csllPaid" name="csllPaid" 
                                   class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 outline-none" 
                                   placeholder="0,00" value="{{ e($irpfFieldValue('csllPaid', $state['csllPaid'] ?? '')) }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1" for="ownershipPercentage">Sua Participação (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" id="ownershipPercentage" name="ownershipPercentage" 
                                   class="w-full p-3 border border-neutral-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 outline-none" 
                                   placeholder="100" value="{{ e($irpfFieldValue('ownershipPercentage', $state['ownershipPercentage'] ?? 100)) }}">
                            <span class="absolute right-3 top-3 text-neutral-400">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-8 flex justify-between">
            <button type="button" onclick="nextStep(4)" class="px-6 py-3 text-neutral-600 font-medium hover:text-neutral-900 transition-colors">← Voltar</button>
            <button type="submit" class="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-md flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Calcular Resultado
            </button>
        </div>
    </div>
</form>
