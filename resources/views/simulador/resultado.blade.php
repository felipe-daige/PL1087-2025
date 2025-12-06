@extends('layouts.app')

@section('title', 'Resultado da Simulação - IRPF 2026')

@section('header-nav')
    <x-simulador.header-nav />
@endsection

@section('content')
@php
    $taxService = $taxCalculatorService;
    $produtos = $produtos ?? [];
    $consolidated = $consolidated ?? [];
    $minimumTax = $produtos['minimumTax'] ?? [];
    $regimeComparison = $produtos['regimeComparison'] ?? [];
    $rentalComparison = $produtos['rentalComparison'] ?? [];
    
    // Valores principais
    $saldoFinal = $displayFinalResult ?? 0;
    $isRestituicao = $isNegativeFinalResult ?? false;
    $aliquotaEfetiva = $effectiveRate ?? 0;
    $rendaTotal = $consolidated['totalIncome'] ?? ($grossTaxable ?? 0);
    $economiaPotencial = $consolidated['potentialSavings'] ?? ($regimeComparison['savings'] ?? 0);
    
    // Breakdown da renda
    $incomeBreakdown = $minimumTax['incomeBreakdown'] ?? [];
    
    // Imposto sobre dividendos (para uso em todo o arquivo)
    $dividendTax = $consolidated['exemptIncome']['dividendTax'] ?? ($consolidated['breakdown']['dividendTax'] ?? ($dividendTax ?? 0));
    
    // Estado do formulário (para compatibilidade)
    $state = $state ?? [];
    
    // Dados para os gráficos
    $chartIncomeData = [
        'taxable' => $incomeBreakdown['taxable'] ?? ($grossTaxable ?? 0),
        'rental' => $incomeBreakdown['rental'] ?? 0,
        'dividends' => $incomeBreakdown['dividends'] ?? 0,
        'jcp' => $incomeBreakdown['jcp'] ?? 0,
        'fii' => $incomeBreakdown['fiiDividends'] ?? 0,
        'investments' => ($incomeBreakdown['financialInvestments'] ?? 0) + ($incomeBreakdown['taxExemptInvestments'] ?? 0),
    ];
    
    $chartRegimeData = $chartData ?? ['simplified' => 0, 'legal' => 0];
    
    $chartIrpfmData = [
        'triggered' => $minimumTax['triggered'] ?? false,
        'rate' => $minimumTax['minimumRate'] ?? 0,
        'traditional' => $minimumTax['traditionalTax'] ?? 0,
        'minimum' => $minimumTax['minimumTaxGross'] ?? 0,
    ];
    
    $chartHoldingData = [
        'hasIncome' => $rentalComparison['hasIncome'] ?? false,
        'pf' => $rentalComparison['pfScenario']['monthlyTax'] ?? 0,
        'pj' => $rentalComparison['pjScenario']['totalMonthlyTax'] ?? 0,
    ];
    
    $chartProjectionData = $rentalComparison['projection'] ?? [];
    
    $baseTax = (($taxSimplified ?? 0) < ($taxLegal ?? 0)) ? ($taxSimplified ?? 0) : ($taxLegal ?? 0);
    $chartTaxBreakdownData = [
        'base' => $baseTax,
        'dividends' => $dividendTax ?? 0,
        'irpfm' => $minimumTax['additionalTaxDue'] ?? 0,
    ];
    
    // Cálculo de alíquotas efetivas comparativas (IRPFM vs Regime Geral)
    $regimeTotalTax = $regimeComparison['totalTax'] ?? 0;
    $irpfmWins = ($minimumTax['triggered'] ?? false) && 
                (($minimumTax['minimumTaxNet'] ?? 0) > $regimeTotalTax);
    $effectiveRateGeneral = $rendaTotal > 0 
        ? ($regimeTotalTax / $rendaTotal) * 100 
        : 0;
    $effectiveRateIRPFM = ($minimumTax['triggered'] ?? false) && $rendaTotal > 0
        ? (($minimumTax['minimumTaxNet'] ?? 0) / $rendaTotal) * 100
        : 0;
    $rateDifference = abs($effectiveRateIRPFM - $effectiveRateGeneral);
    
    // Dados para gráfico Renda vs. Imposto
    $chartIncomeVsTaxData = [
        'income' => $rendaTotal,
        'tax' => $consolidated['totalTax'] ?? 0,
    ];
@endphp

{{-- Hero Section - Azul --}}
<div class="bg-gradient-to-r from-blue-600 to-blue-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-8">
            <div>
                <p class="text-blue-200 text-xs font-medium uppercase tracking-widest mb-3">Resultado da Simulação</p>
                <h1 class="text-3xl md:text-4xl font-semibold text-white mb-1">IRPF 2026</h1>
                <p class="text-blue-100 text-sm">Lei 15.270/2025</p>
            </div>
            
            {{-- Card do Resultado Final --}}
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 md:p-8 border border-white/20">
                <p class="text-blue-100 text-xs font-medium uppercase tracking-wider mb-2">
                    {{ $isRestituicao ? 'Valor a Restituir' : 'Imposto a Pagar' }}
                </p>
                <p class="text-4xl md:text-5xl font-semibold {{ $isRestituicao ? 'text-green-300' : 'text-white' }}">
                    {{ $taxService->irpfCurrency($saldoFinal) }}
                </p>
                <div class="flex items-center gap-2 mt-4">
                    @if($isRestituicao)
                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-green-500/30 text-green-100">
                            Restituição
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-red-500/30 text-red-100">
                            A Pagar
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Banner de Alerta IRPFM --}}
@if($minimumTax['triggered'] ?? false)
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="bg-gradient-to-r from-red-500 to-orange-500 text-white rounded-lg shadow-lg p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold mb-1">IRPFM Ativado - Imposto Mínimo Aplicável</h3>
                <p class="text-sm text-red-50 mb-3">
                    Sua renda total excedeu R$ 600.000/ano. O Imposto de Renda Mínimo (IRPFM) foi aplicado conforme Art. 4º da Lei 15.270/2025.
                </p>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">Alíquota Mínima:</span>
                        <span class="font-bold text-lg">{{ number_format($minimumTax['minimumRate'] ?? 0, 2, ',', '.') }}%</span>
                    </div>
                    @if($irpfmWins)
                    <div class="flex items-center gap-2">
                        <span class="font-medium">Regime Aplicado:</span>
                        <span class="font-bold bg-white/20 px-2 py-1 rounded">IRPFM</span>
                    </div>
                    @endif
                </div>
            </div>
            <a href="#content-irpfm" onclick="showTab('irpfm'); return false;" class="flex-shrink-0 bg-white/20 hover:bg-white/30 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm">
                Ver Detalhes →
            </a>
        </div>
    </div>
</div>
@endif

{{-- Cards de Métricas --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Alíquota Efetiva --}}
        <div class="bg-white rounded-lg shadow-sm border-t-4 {{ ($minimumTax['triggered'] ?? false) && $irpfmWins ? 'border-red-400' : 'border-blue-400' }} p-5">
            <p class="text-neutral-500 text-xs font-medium uppercase tracking-wider mb-2">Alíquota Efetiva</p>
            @if(($minimumTax['triggered'] ?? false) && $effectiveRateIRPFM > 0)
                {{-- Mostrar comparação quando IRPFM está ativo --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-neutral-500">Regime Geral:</span>
                        <span class="text-lg font-semibold {{ !$irpfmWins ? 'text-green-600' : 'text-neutral-600' }}">
                            {{ number_format($effectiveRateGeneral, 2, ',', '.') }}%
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-neutral-500">IRPFM:</span>
                        <span class="text-lg font-semibold {{ $irpfmWins ? 'text-red-600' : 'text-neutral-600' }}">
                            {{ number_format($effectiveRateIRPFM, 2, ',', '.') }}%
                        </span>
                    </div>
                    @if($rateDifference > 0.1)
                    <div class="pt-2 border-t border-neutral-200">
                        <p class="text-xs text-neutral-500">
                            Diferença: 
                            <span class="font-medium {{ $irpfmWins ? 'text-red-600' : 'text-green-600' }}">
                                {{ $irpfmWins ? '+' : '-' }}{{ number_format($rateDifference, 2, ',', '.') }}%
                            </span>
                        </p>
                    </div>
                    @endif
                </div>
            @else
                {{-- Mostrar valor único quando IRPFM não está ativo --}}
                <p class="text-2xl font-semibold text-neutral-900">{{ number_format($aliquotaEfetiva, 2, ',', '.') }}%</p>
            @endif
        </div>

        {{-- Renda Total --}}
        <div class="bg-white rounded-lg shadow-sm border-t-4 border-blue-400 p-5">
            <p class="text-neutral-500 text-xs font-medium uppercase tracking-wider mb-2">Renda Total</p>
            <p class="text-2xl font-semibold text-neutral-900">{{ $taxService->irpfCurrency($rendaTotal) }}</p>
        </div>

        {{-- Economia Potencial --}}
        <div class="bg-white rounded-lg shadow-sm border-t-4 border-blue-400 p-5">
            <p class="text-neutral-500 text-xs font-medium uppercase tracking-wider mb-2">Economia</p>
            <p class="text-2xl font-semibold text-neutral-900">{{ $taxService->irpfCurrency($economiaPotencial) }}</p>
        </div>

        {{-- Status IRPFM --}}
        <div class="bg-white rounded-lg shadow-sm border-t-4 border-blue-400 p-5">
            <p class="text-neutral-500 text-xs font-medium uppercase tracking-wider mb-2">IRPFM</p>
            @if($minimumTax['triggered'] ?? false)
                <p class="text-lg font-semibold text-red-600">Ativado</p>
                <p class="text-xs text-neutral-500 mt-1">{{ number_format($minimumTax['minimumRate'] ?? 0, 1, ',', '.') }}%</p>
            @else
                <p class="text-lg font-semibold text-green-600">Não Aplicável</p>
                <p class="text-xs text-neutral-500 mt-1">&lt; R$ 600k/ano</p>
            @endif
        </div>
    </div>
</div>

{{-- Conteúdo Principal --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Coluna Principal (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Gráfico de Composição da Renda --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-6">Composição da Renda</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="chart-container h-56">
                        <canvas id="incomeChart"></canvas>
                    </div>
                    <div class="space-y-2">
                        @php
                            $incomeItems = [
                                ['label' => 'Salários/Pró-labore', 'value' => $incomeBreakdown['taxable'] ?? ($grossTaxable ?? 0), 'color' => 'bg-blue-500'],
                                ['label' => 'Aluguéis', 'value' => $incomeBreakdown['rental'] ?? 0, 'color' => 'bg-blue-400'],
                                ['label' => 'Dividendos', 'value' => $incomeBreakdown['dividends'] ?? 0, 'color' => 'bg-blue-300'],
                                ['label' => 'JCP', 'value' => $incomeBreakdown['jcp'] ?? 0, 'color' => 'bg-neutral-400'],
                                ['label' => 'FIIs', 'value' => $incomeBreakdown['fiiDividends'] ?? 0, 'color' => 'bg-neutral-300'],
                                ['label' => 'Investimentos', 'value' => ($incomeBreakdown['financialInvestments'] ?? 0) + ($incomeBreakdown['taxExemptInvestments'] ?? 0), 'color' => 'bg-neutral-200'],
                            ];
                        @endphp
                        @foreach($incomeItems as $item)
                            @if($item['value'] > 0)
                            <div class="flex items-center justify-between py-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full {{ $item['color'] }}"></div>
                                    <span class="text-sm text-neutral-600">{{ $item['label'] }}</span>
                                </div>
                                <span class="text-sm font-medium text-neutral-900">{{ $taxService->irpfCurrency($item['value']) }}</span>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Tabs dos Produtos --}}
            <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
                {{-- Tab Navigation --}}
                <div class="flex border-b border-neutral-200">
                    <button type="button" onclick="showTab('regime')" id="tab-regime" 
                            class="result-tab flex-1 px-4 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-500 transition-colors">
                        Regimes
                    </button>
                    <button type="button" onclick="showTab('irpfm')" id="tab-irpfm" 
                            class="result-tab flex-1 px-4 py-3 text-sm font-medium text-neutral-500 hover:text-neutral-700 border-b-2 border-transparent transition-colors">
                        IRPFM
                    </button>
                    <button type="button" onclick="showTab('holding')" id="tab-holding" 
                            class="result-tab flex-1 px-4 py-3 text-sm font-medium text-neutral-500 hover:text-neutral-700 border-b-2 border-transparent transition-colors">
                        PF vs PJ
                    </button>
                </div>

                {{-- Tab Contents --}}
                <div class="p-6">
                    {{-- Tab 1: Comparativo de Regimes --}}
                    <div id="content-regime" class="result-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-neutral-700 mb-4">Simplificado vs. Completo</h3>
                                <div class="chart-container h-40">
                                    <canvas id="regimeChart"></canvas>
                                </div>
                            </div>
                            <div class="space-y-4">
                                {{-- Recomendação --}}
                                <div class="p-4 rounded-lg border {{ ($isSimplifiedBetter ?? false) ? 'border-blue-200 bg-blue-50' : 'border-green-200 bg-green-50' }}">
                                    <p class="text-sm font-medium {{ ($isSimplifiedBetter ?? false) ? 'text-blue-800' : 'text-green-800' }} mb-1">
                                        @if($isSimplifiedBetter ?? false)
                                            Simplificado Recomendado
                                        @else
                                            Deduções Legais Recomendadas
                                        @endif
                                    </p>
                                    <p class="text-sm {{ ($isSimplifiedBetter ?? false) ? 'text-blue-600' : 'text-green-600' }}">
                                        Economia de {{ $taxService->irpfCurrency($regimeComparison['savings'] ?? abs(($taxSimplified ?? 0) - ($taxLegal ?? 0))) }}
                                    </p>
                                </div>

                                {{-- Detalhes --}}
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">Simplificado:</span>
                                        <span class="font-medium text-neutral-900">{{ $taxService->irpfCurrency($taxSimplified ?? 0) }}</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">Completo:</span>
                                        <span class="font-medium text-neutral-900">{{ $taxService->irpfCurrency($taxLegal ?? 0) }}</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">Desconto Simplificado:</span>
                                        <span class="font-medium text-neutral-900">{{ $taxService->irpfCurrency($simplifiedDiscount ?? 0) }}</span>
                                    </div>
                                    <div class="flex justify-between py-2">
                                        <span class="text-neutral-500">Deduções Legais:</span>
                                        <span class="font-medium text-neutral-900">{{ $taxService->irpfCurrency($totalDeductions ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: IRPFM --}}
                    <div id="content-irpfm" class="result-content hidden">
                        @if(!($minimumTax['triggered'] ?? false))
                            <div class="text-center py-10">
                                <div class="w-12 h-12 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-neutral-900 mb-2">IRPFM Não Aplicável</h3>
                                <p class="text-neutral-500 text-sm max-w-sm mx-auto">
                                    Renda abaixo de R$ 600.000/ano.
                                </p>
                                @if(isset($minimumTax['distanceToThreshold']))
                                <p class="text-xs text-neutral-400 mt-3">
                                    Distância: {{ $taxService->irpfCurrency($minimumTax['distanceToThreshold']) }}
                                </p>
                                @endif
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-sm font-medium text-neutral-700 mb-4">Alíquota Mínima</h3>
                                    <div class="chart-container h-40">
                                        <canvas id="irpfmGauge"></canvas>
                                    </div>
                                    <p class="text-center text-sm text-neutral-500 mt-2">
                                        {{ number_format($minimumTax['minimumRate'] ?? 0, 2, ',', '.') }}%
                                    </p>
                                </div>
                                <div class="space-y-3">
                                    <div class="p-3 rounded border border-red-200 bg-red-50">
                                        <p class="text-sm font-medium text-red-800">Imposto Mínimo Ativado</p>
                                        <p class="text-xs text-red-600 mt-0.5">Art. 4º Lei 15.270/2025</p>
                                    </div>
                                    
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between py-2 border-b border-neutral-100">
                                            <span class="text-neutral-500">Renda Total:</span>
                                            <span class="font-medium">{{ $taxService->irpfCurrency($minimumTax['totalIncome'] ?? 0) }}</span>
                                        </div>
                                        <div class="flex justify-between py-2 border-b border-neutral-100">
                                            <span class="text-neutral-500">IRPFM Bruto:</span>
                                            <span class="font-medium">{{ $taxService->irpfCurrency($minimumTax['minimumTaxGross'] ?? 0) }}</span>
                                        </div>
                                        @if(($minimumTax['pjTaxCredit'] ?? 0) > 0)
                                        <div class="flex justify-between py-2 border-b border-neutral-100 text-green-600">
                                            <span>(-) Crédito PJ:</span>
                                            <span class="font-medium">{{ $taxService->irpfCurrency($minimumTax['pjTaxCredit']) }}</span>
                                        </div>
                                        @endif
                                        <div class="flex justify-between py-2 border-b border-neutral-100">
                                            <span class="text-neutral-500">Imposto Tradicional:</span>
                                            <span class="font-medium">{{ $taxService->irpfCurrency($minimumTax['traditionalTax'] ?? 0) }}</span>
                                        </div>
                                        @if(($minimumTax['additionalTaxDue'] ?? 0) > 0)
                                        <div class="flex justify-between py-2 text-red-600">
                                            <span class="font-medium">Complemento:</span>
                                            <span class="font-semibold">+ {{ $taxService->irpfCurrency($minimumTax['additionalTaxDue']) }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Tabela: Composição da Renda Total --}}
                            <div class="mt-6 border-t border-neutral-200 pt-4">
                                <h5 class="text-sm font-semibold text-neutral-700 mb-3">Composição da Renda Total</h5>
                                <div class="space-y-2 text-sm">
                                    @if(($incomeBreakdown['taxable'] ?? 0) > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">Salários/Pró-labore/13º:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($incomeBreakdown['taxable'] ?? 0) }}</span>
                                    </div>
                                    @endif
                                    @if(($incomeBreakdown['rental'] ?? 0) > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">Aluguéis:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($incomeBreakdown['rental'] ?? 0) }}</span>
                                    </div>
                                    @endif
                                    @if(($consolidated['exemptIncome']['dividendsTotal'] ?? ($incomeBreakdown['dividends'] ?? 0)) > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500 font-medium">Lucros e Dividendos Totais:</span>
                                        <span class="font-semibold text-blue-600">{{ $taxService->irpfCurrency($consolidated['exemptIncome']['dividendsTotal'] ?? ($incomeBreakdown['dividends'] ?? 0)) }}</span>
                                    </div>
                                    @endif
                                    @if(($incomeBreakdown['jcp'] ?? 0) > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">JCP:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($incomeBreakdown['jcp'] ?? 0) }}</span>
                                    </div>
                                    @endif
                                    @if(($incomeBreakdown['financialInvestments'] ?? 0) > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">Aplicações Financeiras:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($incomeBreakdown['financialInvestments'] ?? 0) }}</span>
                                    </div>
                                    @endif
                                    @if(($incomeBreakdown['taxExemptInvestments'] ?? 0) > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">LCI/LCA/CRI/CRA:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($incomeBreakdown['taxExemptInvestments'] ?? 0) }}</span>
                                    </div>
                                    @endif
                                    @if(($incomeBreakdown['fiiDividends'] ?? 0) > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">Dividendos de FIIs:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($incomeBreakdown['fiiDividends'] ?? 0) }}</span>
                                    </div>
                                    @endif
                                    @if(($incomeBreakdown['otherExempt'] ?? 0) > 0)
                                    <div class="flex justify-between py-2">
                                        <span class="text-neutral-500">Outros Isentos:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($incomeBreakdown['otherExempt'] ?? 0) }}</span>
                                    </div>
                                    @endif
                                    <div class="flex justify-between py-2 pt-3 border-t-2 border-neutral-300 mt-2">
                                        <span class="text-neutral-700 font-semibold">Total (Base IRPFM):</span>
                                        <span class="font-bold text-neutral-900">{{ $taxService->irpfCurrency($minimumTax['totalIncome'] ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Tabela: Impostos Já Pagos / Retidos --}}
                            <div class="mt-6 border-t border-neutral-200 pt-4">
                                <h5 class="text-sm font-semibold text-neutral-700 mb-3">Impostos Já Pagos / Retidos</h5>
                                <div class="space-y-2 text-sm">
                                    @php
                                        $state = $state ?? [];
                                        $totalIrrfRetido = $state['totalIrrfRetido'] ?? 0;
                                        $taxPaidOther = ($state['taxPaid'] ?? 0) - $totalIrrfRetido;
                                        $dividendTaxTable = $consolidated['exemptIncome']['dividendTax'] ?? ($consolidated['breakdown']['dividendTax'] ?? 0);
                                        $jcpTax = $consolidated['exemptIncome']['jcpTax'] ?? ($consolidated['breakdown']['jcpTax'] ?? 0);
                                        $irrfJcpWithheld = $consolidated['exemptIncome']['irrfJcpWithheld'] ?? 0;
                                        $irrfExclusiveOther = $consolidated['exemptIncome']['irrfExclusiveOther'] ?? 0;
                                    @endphp
                                    @if($totalIrrfRetido > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">IRRF Retido (Salários/Pró-labore):</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($totalIrrfRetido) }}</span>
                                    </div>
                                    @endif
                                    @if($taxPaidOther > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">Carnê-Leão:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($taxPaidOther) }}</span>
                                    </div>
                                    @endif
                                    @if($dividendTaxTable > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500 font-medium">IRRF sobre Dividendos (10% sobre excedente):</span>
                                        <span class="font-semibold text-blue-600">{{ $taxService->irpfCurrency($dividendTaxTable) }}</span>
                                    </div>
                                    @endif
                                    @if($irrfJcpWithheld > 0 || $jcpTax > 0)
                                    <div class="flex justify-between py-2 border-b border-neutral-100">
                                        <span class="text-neutral-500">IRRF sobre JCP:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($irrfJcpWithheld > 0 ? $irrfJcpWithheld : $jcpTax) }}</span>
                                    </div>
                                    @endif
                                    @if($irrfExclusiveOther > 0)
                                    <div class="flex justify-between py-2">
                                        <span class="text-neutral-500">IRRF sobre Outras Aplicações:</span>
                                        <span class="font-medium">{{ $taxService->irpfCurrency($irrfExclusiveOther) }}</span>
                                    </div>
                                    @endif
                                    <div class="flex justify-between py-2 pt-3 border-t-2 border-neutral-300 mt-2">
                                        <span class="text-neutral-700 font-semibold">Total Impostos Pagos:</span>
                                        <span class="font-bold text-neutral-900">{{ $taxService->irpfCurrency($consolidated['taxPaid'] ?? ($state['taxPaid'] ?? 0)) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Tab 3: PF vs PJ --}}
                    <div id="content-holding" class="result-content hidden">
                        @if(!($rentalComparison['hasIncome'] ?? false))
                            <div class="text-center py-10">
                                <div class="w-12 h-12 mx-auto bg-neutral-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-neutral-900 mb-2">Nenhum Imóvel</h3>
                                <p class="text-neutral-500 text-sm max-w-sm mx-auto">
                                    Adicione imóveis de aluguel para ver esta análise.
                                </p>
                            </div>
                        @else
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <h3 class="text-sm font-medium text-neutral-700 mb-4">Comparativo Mensal</h3>
                                        <div class="chart-container h-40">
                                            <canvas id="holdingChart"></canvas>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        {{-- Cards PF vs PJ --}}
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="p-4 rounded-lg border {{ !($rentalComparison['comparison']['isPJBetter'] ?? false) ? 'border-green-300 bg-green-50' : 'border-neutral-200' }}">
                                                <p class="text-xs text-neutral-500 uppercase tracking-wider">PF</p>
                                                <p class="text-lg font-semibold text-neutral-900 mt-1">{{ $taxService->irpfCurrency($rentalComparison['pfScenario']['monthlyTax'] ?? 0) }}</p>
                                                <p class="text-xs text-neutral-400">/mês</p>
                                            </div>
                                            <div class="p-4 rounded-lg border {{ ($rentalComparison['comparison']['isPJBetter'] ?? false) ? 'border-green-300 bg-green-50' : 'border-neutral-200' }}">
                                                <p class="text-xs text-neutral-500 uppercase tracking-wider">PJ</p>
                                                <p class="text-lg font-semibold text-neutral-900 mt-1">{{ $taxService->irpfCurrency($rentalComparison['pjScenario']['totalMonthlyTax'] ?? 0) }}</p>
                                                <p class="text-xs text-neutral-400">/mês</p>
                                            </div>
                                        </div>

                                        @if(($rentalComparison['comparison']['isPJBetter'] ?? false) && ($rentalComparison['comparison']['annualDifference'] ?? 0) > 0)
                                        <div class="p-3 rounded border border-green-200 bg-green-50">
                                            <p class="text-sm font-medium text-green-800">
                                                Economia: {{ $taxService->irpfCurrency($rentalComparison['comparison']['annualDifference']) }}/ano
                                            </p>
                                            @if(isset($rentalComparison['feasibility']['paybackMonths']))
                                            <p class="text-xs text-green-600 mt-1">
                                                Payback: {{ $rentalComparison['feasibility']['paybackMonths'] }} meses
                                            </p>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                @if(isset($rentalComparison['projection']))
                                <div>
                                    <h3 class="text-sm font-medium text-neutral-700 mb-4">Projeção 12 meses</h3>
                                    <div class="chart-container h-56">
                                        <canvas id="projectionChart"></canvas>
                                    </div>
                                </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Gráfico de Breakdown do Imposto --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-6">Composição do Imposto</h2>
                <div class="chart-container h-56">
                    <canvas id="taxBreakdownChart"></canvas>
                </div>
            </div>

            {{-- Gráfico Renda Total vs. Imposto Total --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-6">Renda Total vs. Imposto Total</h2>
                <div class="chart-container h-64">
                    <canvas id="incomeVsTaxChart"></canvas>
                </div>
                <div class="mt-4 flex items-center justify-center gap-6 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-blue-500 rounded"></div>
                        <span class="text-neutral-600">Renda Total</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-red-500 rounded"></div>
                        <span class="text-neutral-600">Imposto Total</span>
                    </div>
                    @if($rendaTotal > 0)
                    <div class="flex items-center gap-2">
                        <span class="text-neutral-500">Alíquota Efetiva:</span>
                        <span class="font-semibold text-neutral-900">{{ number_format($aliquotaEfetiva, 2, ',', '.') }}%</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Coluna Lateral (1/3) --}}
        <div class="space-y-6">
            
            {{-- Detalhamento do Cálculo --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-4">Detalhamento</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-2 border-b border-neutral-100">
                        <span class="text-neutral-500">Renda Bruta:</span>
                        <span class="font-medium text-neutral-900">{{ $taxService->irpfCurrency($grossTaxable ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-neutral-100">
                        <span class="text-neutral-500">(-) Deduções:</span>
                        <span class="font-medium text-green-600">{{ $taxService->irpfCurrency(max($simplifiedDiscount ?? 0, $totalDeductions ?? 0)) }}</span>
                    </div>
                    @php
                        $dividendTax = $consolidated['exemptIncome']['dividendTax'] ?? ($consolidated['breakdown']['dividendTax'] ?? ($dividendTax ?? 0));
                    @endphp
                    @if($dividendTax > 0)
                    <div class="flex justify-between py-2 border-b border-neutral-100">
                        <span class="text-neutral-500">(+) Imposto s/ Dividendos:</span>
                        <span class="font-medium text-neutral-900">{{ $taxService->irpfCurrency($dividendTax) }}</span>
                    </div>
                    @endif
                    @if(($minimumTax['additionalTaxDue'] ?? 0) > 0)
                    <div class="flex justify-between py-2 border-b border-neutral-100">
                        <span class="text-neutral-500">(+) IRPFM:</span>
                        <span class="font-medium text-red-600">{{ $taxService->irpfCurrency($minimumTax['additionalTaxDue']) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-2 border-b border-neutral-100">
                        <span class="text-neutral-500">Imposto Devido:</span>
                        <span class="font-semibold text-neutral-900">{{ $taxService->irpfCurrency($totalTaxLiability ?? 0) }}</span>
                    </div>
                    @php
                        // Quando IRPFM vence, todos os impostos são abatidos (incluindo dividendos)
                        // Quando Regime Geral vence, apenas impostos dedutíveis são abatidos
                        $irpfmWins = ($minimumTax['triggered'] ?? false) && 
                                    (($minimumTax['minimumTaxNet'] ?? 0) > ($minimumTax['traditionalTax'] ?? 0));
                        $taxPaidDisplay = $irpfmWins 
                            ? ($consolidated['taxPaid'] ?? ($state['taxPaid'] ?? 0))  // Todos os impostos quando IRPFM vence
                            : ($state['taxPaid'] ?? 0);  // Apenas dedutíveis quando Regime Geral vence
                    @endphp
                    <div class="flex justify-between py-2 border-b border-neutral-100">
                        <span class="text-neutral-500">(-) Já Pago:</span>
                        <span class="font-medium text-blue-600">{{ $taxService->irpfCurrency($taxPaidDisplay) }}</span>
                    </div>
                    @if($irpfmWins && ($consolidated['exemptIncome']['dividendTax'] ?? 0) > 0)
                    <div class="flex justify-between py-1 text-xs text-neutral-400 italic pl-2">
                        <span>Inclui: IRRF salários + Carnê-leão + Imposto s/ Dividendos + IRRF JCP + IRRF outras aplicações</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-3 mt-2 border-t border-neutral-200">
                        <span class="font-medium text-neutral-900">Saldo Final:</span>
                        <span class="font-semibold {{ $isRestituicao ? 'text-green-600' : 'text-red-600' }}">
                            {{ $isRestituicao ? '- ' : '' }}{{ $taxService->irpfCurrency($saldoFinal) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Informações da Lei --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-4">Lei 15.270/2025</h2>
                <div class="space-y-4 text-sm">
                    <div class="pb-3 border-b border-neutral-100">
                        <p class="font-medium text-neutral-700 mb-1">Art. 3º-A</p>
                        <p class="text-neutral-500 text-xs">Isenção até R$ 5.000/mês</p>
                    </div>
                    <div class="pb-3 border-b border-neutral-100">
                        <p class="font-medium text-neutral-700 mb-1">Art. 4º - IRPFM</p>
                        <p class="text-neutral-500 text-xs">Imposto Mínimo para rendas &gt; R$ 600k/ano</p>
                    </div>
                    <div class="pb-3 border-b border-neutral-100">
                        <p class="font-medium text-neutral-700 mb-1">Art. 5º - Dividendos</p>
                        <p class="text-neutral-500 text-xs">10% sobre excedente &gt; R$ 600k/ano</p>
                    </div>
                    <div>
                        <p class="font-medium text-neutral-700 mb-1">Trava PJ</p>
                        <p class="text-neutral-500 text-xs">IRPJ/CSLL abatidos do IRPFM</p>
                    </div>
                </div>
            </div>

            {{-- Alertas --}}
            @if(count($alerts ?? []) > 0)
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-4">Alertas</h2>
                <div class="space-y-2">
                    @foreach($alerts as $alert)
                    <div class="flex items-start gap-2 text-sm text-neutral-600">
                        <span class="text-neutral-400 mt-0.5">•</span>
                        <span>{{ $alert }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Ações --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <div class="space-y-3">
                    <a href="{{ route('simulador.index') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition-colors">
                        Nova Simulação
                    </a>
                    <button onclick="window.print()" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 border border-neutral-300 text-neutral-700 text-sm font-medium rounded-lg hover:bg-neutral-50 transition-colors">
                        Imprimir
                    </button>
                    <a href="{{ route('simulador.guia') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-neutral-500 text-sm font-medium hover:text-neutral-700 transition-colors">
                        Ver Guia da Lei
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.chartData = {
        incomeVsTax: {
            income: {{ $chartIncomeVsTaxData['income'] ?? 0 }},
            tax: {{ $chartIncomeVsTaxData['tax'] ?? 0 }}
        },
        income: @json($chartIncomeData),
        regime: @json($chartRegimeData),
        irpfm: @json($chartIrpfmData),
        holding: @json($chartHoldingData),
        projection: @json($chartProjectionData),
        taxBreakdown: @json($chartTaxBreakdownData),
    };
</script>
@vite('resources/js/resultado.js')
@endpush

@endsection
