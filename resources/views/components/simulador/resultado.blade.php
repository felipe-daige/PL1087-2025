@php
    // Acesso aos dados dos produtos
    $produtos = $produtos ?? [];
    $consolidated = $consolidated ?? [];
    $regimeComparison = $produtos['regimeComparison'] ?? [];
    $minimumTax = $produtos['minimumTax'] ?? [];
    $rentalComparison = $produtos['rentalComparison'] ?? [];
    
    // Valores padrão para variáveis que podem não estar definidas
    $isSimplifiedBetter = $isSimplifiedBetter ?? false;
    $taxSimplified = $taxSimplified ?? 0;
    $taxLegal = $taxLegal ?? 0;
@endphp

<div class="sticky top-24 space-y-4">
    {{-- ========================================
         CARD PRINCIPAL: RESULTADO FINAL
         ======================================== --}}
    <div id="resultCard" class="bg-white p-6 rounded-xl shadow-lg border-l-4 {{ $cardBorder }} transition-all">
        @if (! $isNegativeFinalResult)
            <h3 class="text-xs font-bold text-neutral-500 uppercase tracking-wider mb-1">Resultado Estimado</h3>
        @endif
        <div class="flex items-baseline gap-2">
            <span class="text-4xl font-black text-neutral-800" id="finalResultValue">{{ $taxCalculatorService->irpfCurrency($displayFinalResult) }}</span>
        </div>
        <p id="resultStatus" class="{{ $resultClass }}">{{ $resultLabel }}</p>
        
        {{-- Métricas Resumidas --}}
        <div class="mt-4 pt-4 border-t border-neutral-100 grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-neutral-500 block text-xs">Alíquota Efetiva</span>
                <span id="effectiveRate" class="font-bold text-lg text-neutral-800">{{ number_format($effectiveRate, 2, ',', '.') }}%</span>
            </div>
            <div>
                <span class="text-neutral-500 block text-xs">Renda Total</span>
                <span class="font-semibold text-neutral-800">{{ $taxCalculatorService->irpfCurrency($consolidated['totalIncome'] ?? $grossTaxable) }}</span>
            </div>
        </div>

        {{-- Indicador IRPFM --}}
        @if(($minimumTax['triggered'] ?? false))
        <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex items-center gap-2 text-amber-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span class="font-semibold text-sm">IRPFM Ativado</span>
            </div>
            <p class="text-xs text-amber-700 mt-1">Alíquota mínima: {{ number_format($minimumTax['minimumRate'] ?? 0, 1, ',', '.') }}%</p>
        </div>
        @endif
    </div>

    {{-- ========================================
         ABAS DOS PRODUTOS
         ======================================== --}}
    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
        {{-- Tab Navigation --}}
        <div class="flex border-b border-neutral-200">
            <button type="button" onclick="showProductTab('regime')" id="tab-regime" 
                    class="product-tab flex-1 px-4 py-3 text-sm font-medium text-brand-700 bg-brand-50 border-b-2 border-brand-600 transition-colors">
                Regime
            </button>
            <button type="button" onclick="showProductTab('irpfm')" id="tab-irpfm" 
                    class="product-tab flex-1 px-4 py-3 text-sm font-medium text-neutral-500 hover:text-neutral-700 transition-colors">
                IRPFM
            </button>
            <button type="button" onclick="showProductTab('holding')" id="tab-holding" 
                    class="product-tab flex-1 px-4 py-3 text-sm font-medium text-neutral-500 hover:text-neutral-700 transition-colors">
                PF vs PJ
            </button>
        </div>

        {{-- Tab Content --}}
        <div class="p-4">
            {{-- ========================================
                 PRODUTO 1: COMPARATIVO DE REGIMES
                 ======================================== --}}
            <div id="content-regime" class="product-content">
                <h4 class="text-sm font-semibold text-neutral-800 mb-3">Simplificado vs. Completo</h4>
                
                {{-- Gráfico de Barras --}}
                <div class="chart-container h-40 mb-4">
                    <canvas id="comparisonChart"></canvas>
                </div>

                {{-- Recomendação --}}
                <div class="p-3 rounded-lg {{ ($isSimplifiedBetter ?? false) ? 'bg-brand-50 border border-brand-200' : 'bg-green-50 border border-green-200' }}">
                    <p class="text-sm">{!! $recommendationText !!}</p>
                </div>

                {{-- Detalhamento --}}
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Desconto Simplificado:</span>
                        <span class="font-medium text-brand-700">- {{ $taxCalculatorService->irpfCurrency($simplifiedDiscount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Deduções Legais:</span>
                        <span class="font-medium text-green-700">- {{ $taxCalculatorService->irpfCurrency($totalDeductions) }}</span>
                    </div>
                    @if($regimeComparison['savings'] ?? 0 > 0)
                    <div class="flex justify-between pt-2 border-t border-neutral-100">
                        <span class="text-neutral-700 font-medium">Economia:</span>
                        <span class="font-bold text-green-600">{{ $taxCalculatorService->irpfCurrency($regimeComparison['savings'] ?? abs($taxSimplified - $taxLegal)) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- ========================================
                 PRODUTO 2: IRPFM (IMPOSTO MÍNIMO)
                 ======================================== --}}
            <div id="content-irpfm" class="product-content hidden">
                <h4 class="text-sm font-semibold text-neutral-800 mb-3">Imposto Mínimo (Lei 15.270)</h4>
                
                @if(!($minimumTax['triggered'] ?? false))
                    {{-- Não atingiu gatilho --}}
                    <div class="text-center py-6">
                        <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <p class="text-neutral-600 font-medium">IRPFM não aplicável</p>
                        <p class="text-sm text-neutral-400 mt-1">Renda abaixo de R$ 600.000/ano</p>
                        @if(isset($minimumTax['distanceToThreshold']))
                        <p class="text-xs text-neutral-400 mt-2">
                            Distância do gatilho: {{ $taxCalculatorService->irpfCurrency($minimumTax['distanceToThreshold']) }}
                        </p>
                        @endif
                    </div>
                @else
                    {{-- IRPFM Ativado --}}
                    <div class="space-y-4">
                        {{-- Gauge de Alíquota --}}
                        <div class="relative h-32">
                            <canvas id="irpfmGauge"></canvas>
                        </div>

                        {{-- Breakdown --}}
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Renda Total (Base IRPFM):</span>
                                <span class="font-medium">{{ $taxCalculatorService->irpfCurrency($minimumTax['totalIncome'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Alíquota Mínima:</span>
                                <span class="font-medium text-amber-600">{{ number_format($minimumTax['minimumRate'] ?? 0, 2, ',', '.') }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-500">IRPFM Bruto:</span>
                                <span class="font-medium">{{ $taxCalculatorService->irpfCurrency($minimumTax['minimumTaxGross'] ?? 0) }}</span>
                            </div>
                            
                            @if(($minimumTax['pjTaxCredit'] ?? 0) > 0)
                            <div class="flex justify-between text-green-700 bg-green-50 p-2 rounded -mx-2">
                                <span>(-) Crédito IRPJ/CSLL:</span>
                                <span class="font-medium">{{ $taxCalculatorService->irpfCurrency($minimumTax['pjTaxCredit']) }}</span>
                            </div>
                            @endif

                            <div class="flex justify-between pt-2 border-t border-neutral-100">
                                <span class="text-neutral-500">Imposto Tradicional:</span>
                                <span class="font-medium">{{ $taxCalculatorService->irpfCurrency($minimumTax['traditionalTax'] ?? 0) }}</span>
                            </div>

                            @if(($minimumTax['additionalTaxDue'] ?? 0) > 0)
                            <div class="flex justify-between text-red-700 bg-red-50 p-2 rounded -mx-2 font-medium">
                                <span>Complemento IRPFM:</span>
                                <span>+ {{ $taxCalculatorService->irpfCurrency($minimumTax['additionalTaxDue']) }}</span>
                            </div>
                            @else
                            <div class="flex justify-between text-green-700 bg-green-50 p-2 rounded -mx-2">
                                <span>Complemento:</span>
                                <span class="font-medium">Não há</span>
                            </div>
                            @endif
                        </div>

                        {{-- Mensagem --}}
                        @if(isset($minimumTax['message']))
                        <p class="text-xs text-neutral-500 italic">{{ $minimumTax['message'] }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ========================================
                 PRODUTO 3: COMPARATIVO PF vs HOLDING
                 ======================================== --}}
            <div id="content-holding" class="product-content hidden">
                <h4 class="text-sm font-semibold text-neutral-800 mb-3">Comparativo: PF vs. PJ</h4>
                
                @if(!($rentalComparison['hasIncome'] ?? false))
                    {{-- Sem imóveis --}}
                    <div class="text-center py-6">
                        <div class="w-16 h-16 mx-auto bg-neutral-100 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                        <p class="text-neutral-600 font-medium">Nenhum imóvel informado</p>
                        <p class="text-sm text-neutral-400 mt-1">Adicione imóveis na Etapa 3 para ver esta análise</p>
                    </div>
                @else
                    {{-- Comparativo --}}
                    <div class="space-y-4">
                        {{-- Gráfico comparativo --}}
                        <div class="chart-container h-32">
                            <canvas id="holdingChart"></canvas>
                        </div>

                        {{-- Cards PF vs PJ --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-lg {{ !($rentalComparison['comparison']['isPJBetter'] ?? false) ? 'bg-green-50 border-2 border-green-300' : 'bg-neutral-50 border border-neutral-200' }}">
                                <p class="text-xs text-neutral-500 uppercase tracking-wider">Pessoa Física</p>
                                <p class="text-lg font-bold text-neutral-800">{{ $taxCalculatorService->irpfCurrency($rentalComparison['pfScenario']['monthlyTax'] ?? 0) }}</p>
                                <p class="text-xs text-neutral-400">/mês</p>
                            </div>
                            <div class="p-3 rounded-lg {{ ($rentalComparison['comparison']['isPJBetter'] ?? false) ? 'bg-green-50 border-2 border-green-300' : 'bg-neutral-50 border border-neutral-200' }}">
                                <p class="text-xs text-neutral-500 uppercase tracking-wider">Pessoa Jurídica</p>
                                <p class="text-lg font-bold text-neutral-800">{{ $taxCalculatorService->irpfCurrency($rentalComparison['pjScenario']['totalMonthlyTax'] ?? 0) }}</p>
                                <p class="text-xs text-neutral-400">/mês</p>
                            </div>
                        </div>

                        {{-- Economia --}}
                        @if(($rentalComparison['comparison']['isPJBetter'] ?? false) && ($rentalComparison['comparison']['monthlyDifference'] ?? 0) > 0)
                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm font-medium text-green-800">
                                Economia potencial: 
                                <span class="font-bold">{{ $taxCalculatorService->irpfCurrency($rentalComparison['comparison']['annualDifference'] ?? 0) }}/ano</span>
                            </p>
                            @if(isset($rentalComparison['feasibility']['paybackMonths']))
                            <p class="text-xs text-green-600 mt-1">
                                Payback estimado: {{ $rentalComparison['feasibility']['paybackMonths'] }} meses
                            </p>
                            @endif
                        </div>
                        @endif

                        {{-- Recomendação --}}
                        @if(isset($rentalComparison['recommendation']))
                        <p class="text-xs text-neutral-500 italic">{{ $rentalComparison['recommendation'] }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================================
         DETALHAMENTO DO CÁLCULO
         ======================================== --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-neutral-200">
        <button type="button" onclick="toggleDetails()" class="w-full flex items-center justify-between text-sm font-semibold text-neutral-800">
            <span>Detalhamento do Cálculo</span>
            <svg id="detailsArrow" class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        
        <div id="detailsContent" class="hidden mt-4 space-y-3 text-sm border-t border-neutral-100 pt-4">
            <div class="flex justify-between">
                <span class="text-neutral-500">Renda Bruta Tributável:</span>
                <span class="font-medium" id="displayGrossIncome">{{ $taxCalculatorService->irpfCurrency($grossTaxable) }}</span>
            </div>
            <div class="flex justify-between text-brand-700">
                <span class="text-neutral-500">Desconto Simplificado:</span>
                <span class="font-medium" id="displaySimplifiedDisc">- {{ $taxCalculatorService->irpfCurrency($simplifiedDiscount) }}</span>
            </div>
            <div class="flex justify-between text-green-600">
                <span class="text-neutral-500">Total Deduções Legais:</span>
                <span class="font-medium" id="displayLegalDed">- {{ $taxCalculatorService->irpfCurrency($totalDeductions) }}</span>
            </div>
            @if($dividendTax > 0)
            <div class="flex justify-between text-amber-600 border-t border-neutral-100 pt-2">
                <span class="text-neutral-500">Imposto s/ Dividendos:</span>
                <span class="font-medium" id="displayDivTax">+ {{ $taxCalculatorService->irpfCurrency($dividendTax) }}</span>
            </div>
            @endif
            @if(($minimumTax['additionalTaxDue'] ?? 0) > 0)
            <div class="flex justify-between text-red-600">
                <span class="text-neutral-500">Complemento IRPFM:</span>
                <span class="font-medium">+ {{ $taxCalculatorService->irpfCurrency($minimumTax['additionalTaxDue']) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-red-600 border-t border-neutral-100 pt-2">
                <span class="text-neutral-500">Imposto Devido Total:</span>
                <span class="font-medium" id="displayTaxDue">{{ $taxCalculatorService->irpfCurrency($totalTaxLiability) }}</span>
            </div>
            <div class="flex justify-between text-neutral-400 text-xs">
                <span>(-) Imposto Já Pago:</span>
                <span id="displayTaxPaid">- {{ $taxCalculatorService->irpfCurrency($taxPaid) }}</span>
            </div>
        </div>
    </div>

    {{-- ========================================
         ALERTAS
         ======================================== --}}
    @if(count($alerts) > 0)
    <div id="alertsContainer" class="space-y-2">
        @foreach($alerts as $alert)
            <div class="bg-amber-50 border border-amber-200 text-amber-900 text-sm p-3 rounded-lg flex items-start gap-2">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ $alert }}</span>
            </div>
        @endforeach
    </div>
    @endif
</div>

