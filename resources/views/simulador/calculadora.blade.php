@extends('layouts.app')

@section('title', 'Simulador IRPF 2026 - Lei 15.270')

@section('header-nav')
    <x-simulador.header-nav />
@endsection

@section('content')
    {{-- Banner informativo --}}
    <div id="calculator-banner" class="view-section bg-gradient-to-r from-brand-50 to-indigo-50 border-b border-brand-100">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 hidden sm:block">
                    <div class="w-12 h-12 rounded-full bg-brand-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-brand-900 mb-2">Sistema de Inteligência Tributária</h2>
                    <p class="text-brand-800 text-sm leading-relaxed max-w-4xl">
                        Simulador baseado na <strong>Lei 15.270/2025</strong> (antigo PL 1.087). Inclui a 
                        <span class="font-semibold">nova isenção até R$ 5.000/mês</span>, 
                        tributação de <span class="font-semibold">dividendos acima de R$ 50k/mês</span> e o 
                        <span class="font-semibold">IRPFM (Imposto Mínimo)</span> para rendas acima de R$ 600k/ano.
                    </p>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Art. 3º-A: Nova Tabela
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                            Art. 4º: IRPFM
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Art. 5º: Dividendos
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Conteúdo principal --}}
    <div id="calculator-view" class="view-section">
        <main class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
            {{-- Formulário --}}
            <div class="lg:col-span-7 xl:col-span-8">
                <x-simulador.form 
                    :state="$state" 
                    :request="$request" 
                    :submitted="$submitted" 
                    :constants="$constants"
                    :educationWarning="$educationWarning"
                />
            </div>

            {{-- Painel de Resultados --}}
            <div class="lg:col-span-5 xl:col-span-4">
                <x-simulador.resultado 
                    :displayFinalResult="$displayFinalResult"
                    :resultLabel="$resultLabel"
                    :resultClass="$resultClass"
                    :cardBorder="$cardBorder"
                    :effectiveRate="$effectiveRate"
                    :chartData="$chartData"
                    :alerts="$alerts"
                    :grossTaxable="$grossTaxable"
                    :simplifiedDiscount="$simplifiedDiscount"
                    :totalDeductions="$totalDeductions"
                    :dividendTax="$dividendTax"
                    :totalTaxLiability="$totalTaxLiability"
                    :taxPaid="$state['taxPaid']"
                    :recommendationText="$recommendationText"
                    :taxCalculatorService="$taxCalculatorService"
                    :isNegativeFinalResult="$isNegativeFinalResult"
                    :isSimplifiedBetter="$isSimplifiedBetter"
                    :taxSimplified="$taxSimplified"
                    :taxLegal="$taxLegal"
                    :produtos="$produtos ?? []"
                    :consolidated="$consolidated ?? []"
                />
            </div>
        </main>
    </div>

    @push('scripts')
        <script>
            // Dados para os gráficos
            window.chartPayload = @json($chartData);
            window.hasSubmission = @json($submitted);
            window.CURRENT_YEAR = {{ $currentYear }};
            window.IRPF_CONSTANTS = @json($constants);
            
            // Dados dos produtos para os gráficos adicionais
            @if(isset($produtos['rentalComparison']) && ($produtos['rentalComparison']['hasIncome'] ?? false))
            window.holdingData = {
                pf: {{ $produtos['rentalComparison']['pfScenario']['monthlyTax'] ?? 0 }},
                pj: {{ $produtos['rentalComparison']['pjScenario']['totalMonthlyTax'] ?? 0 }}
            };
            @else
            window.holdingData = null;
            @endif
        </script>
        @vite('resources/js/simulador.js')
    @endpush
@endsection
