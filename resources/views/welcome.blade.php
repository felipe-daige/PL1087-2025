<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador IRPF 2026 - Lei 15.270</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        neutral: {
                            50: '#fafaf9',
                            100: '#f5f5f4',
                            200: '#e7e5e4',
                            300: '#d6d3d1',
                            400: '#a8a29e',
                            500: '#78716c',
                            600: '#57534e',
                            700: '#44403c',
                            800: '#292524',
                            900: '#1c1917',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #eff6ff;
            color: #0f172a;
        }
        .chart-container {
            position: relative;
            width: 100%;
            max-width: 100%;
            height: 250px;
            max-height: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        @media (min-width: 768px) {
            .chart-container {
                height: 300px;
            }
        }
        .step-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }
        .step-content.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dashboard-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .dashboard-scroll::-webkit-scrollbar-track {
            background: #dbeafe;
        }
        .dashboard-scroll::-webkit-scrollbar-thumb {
            background-color: #93c5fd;
            border-radius: 20px;
        }
        .nav-tab.active {
            color: #1d4ed8;
            border-bottom-color: #2563eb;
        }
        html {
            scroll-behavior: smooth;
        }
        .doc-section {
            scroll-margin-top: 120px;
        }
    </style>
</head>
@php
    $request = request();
    $submitted = $request->isMethod('post');
    $currentYear = 2026;

    $raw = [
        'birthYear' => $request->input('birthYear'),
        'incomeMonthly' => $request->input('incomeMonthly'),
        'income13' => $request->input('income13'),
        'dividendsTotal' => $request->input('dividendsTotal'),
        'dividendsExcess' => $request->input('dividendsExcess'),
        'incomeOther' => $request->input('incomeOther'),
        'taxPaid' => $request->input('taxPaid'),
        'dependents' => $request->input('dependents'),
        'deductionHealth' => $request->input('deductionHealth'),
        'deductionEducation' => $request->input('deductionEducation'),
        'deductionPGBL' => $request->input('deductionPGBL'),
    ];

    if (! function_exists('irpf_to_float')) {
        function irpf_to_float($value): float
        {
            if ($value === null || $value === '') {
                return 0.0;
            }

            if (is_string($value)) {
                $value = str_replace(['.', ' '], ['', ''], $value);
                $value = str_replace(',', '.', $value);
            }

            return (float) $value;
        }
    }

    if (! function_exists('irpf_currency')) {
        function irpf_currency(float $value): string
        {
            $prefix = $value < 0 ? '- ' : '';
            return $prefix . 'R$ ' . number_format(abs($value), 2, ',', '.');
        }
    }

    if (! function_exists('irpf_field_value')) {
        function irpf_field_value($request, string $key, $stateValue, bool $submitted)
        {
            $rawValue = $request->input($key);
            if ($rawValue !== null && $rawValue !== '') {
                return $rawValue;
            }

            return $submitted ? $stateValue : '';
        }
    }

    if (! function_exists('calculateProgressiveTaxIRPF')) {
        function calculateProgressiveTaxIRPF(float $annualBase): float
        {
            if ($annualBase <= 0) {
                return 0.0;
            }

            $monthlyBase = $annualBase / 12;
            $monthlyTax = 0.0;

            if ($monthlyBase <= 5000) {
                $monthlyTax = 0;
            } elseif ($monthlyBase <= 7350) {
                $monthlyTax = ($monthlyBase * 0.075) - 375;
            } elseif ($monthlyBase <= 9250) {
                $monthlyTax = ($monthlyBase * 0.15) - 926.25;
            } elseif ($monthlyBase <= 12000) {
                $monthlyTax = ($monthlyBase * 0.225) - 1620;
            } else {
                $monthlyTax = ($monthlyBase * 0.275) - 2220;
            }

            return max(0, $monthlyTax * 12);
        }
    }

    if (! function_exists('calculateMonthlyTaxIRPF')) {
        function calculateMonthlyTaxIRPF(float $monthlyBase): float
        {
            if ($monthlyBase <= 0) {
                return 0.0;
            }

            if ($monthlyBase <= 5000) {
                return 0.0;
            } elseif ($monthlyBase <= 7350) {
                return max(0, ($monthlyBase * 0.075) - 375);
            } elseif ($monthlyBase <= 9250) {
                return max(0, ($monthlyBase * 0.15) - 926.25);
            } elseif ($monthlyBase <= 12000) {
                return max(0, ($monthlyBase * 0.225) - 1620);
            }

            return max(0, ($monthlyBase * 0.275) - 2220);
        }
    }

    $constants = [
        'deductionPerDependent' => 2275.08,
        'educationCap' => 3561.50,
        'simplifiedRate' => 0.20,
        'simplifiedCap' => 16754.34,
        'dividendTaxRate' => 0.10,
    ];

    $state = [
        'birthYear' => $raw['birthYear'] !== null && $raw['birthYear'] !== '' ? (int) $raw['birthYear'] : null,
        'seriousIllness' => $request->boolean('seriousIllness'),
        'incomeMonthly' => irpf_to_float($raw['incomeMonthly'] ?? null),
        'income13' => irpf_to_float($raw['income13'] ?? null),
        'dividendsTotal' => irpf_to_float($raw['dividendsTotal'] ?? null),
        'hasExcessDividends' => $request->boolean('hasExcessDividends'),
        'dividendsExcess' => irpf_to_float($raw['dividendsExcess'] ?? null),
        'incomeOther' => irpf_to_float($raw['incomeOther'] ?? null),
        'taxPaid' => irpf_to_float($raw['taxPaid'] ?? null),
        'dependents' => max(0, (int) ($raw['dependents'] ?? 0)),
        'deductionHealth' => irpf_to_float($raw['deductionHealth'] ?? null),
        'deductionEducation' => irpf_to_float($raw['deductionEducation'] ?? null),
        'deductionPGBL' => irpf_to_float($raw['deductionPGBL'] ?? null),
        'highIncomeTrigger' => $request->boolean('highIncomeTrigger'),
    ];

    $grossTaxable = ($state['incomeMonthly'] * 12) + ($state['incomeOther'] * 12);
    $age = $state['birthYear'] ? ($currentYear - $state['birthYear']) : 0;

    if ($age >= 65) {
        $grossTaxable = max(0, $grossTaxable - 24000);
    }

    if ($state['seriousIllness']) {
        $grossTaxable = $state['incomeOther'] * 12;
    }

    $simplifiedDiscount = min($grossTaxable * $constants['simplifiedRate'], $constants['simplifiedCap']);
    $baseSimplified = $grossTaxable - $simplifiedDiscount;
    $taxSimplified = calculateProgressiveTaxIRPF(max(0, $baseSimplified));

    $educationWarning = false;
    $educationDeduction = $state['deductionEducation'];
    if ($educationDeduction > $constants['educationCap']) {
        $educationWarning = true;
        $educationDeduction = $constants['educationCap'];
    }

    $pgblCap = max(0, $grossTaxable * 0.12);
    $pgblDeduction = min($state['deductionPGBL'], $pgblCap);

    $totalDeductions = ($state['dependents'] * $constants['deductionPerDependent'])
        + $state['deductionHealth']
        + $educationDeduction
        + $pgblDeduction;

    $baseLegal = max(0, $grossTaxable - $totalDeductions);
    $taxLegal = calculateProgressiveTaxIRPF($baseLegal);

    $dividendTax = ($state['hasExcessDividends'] && $state['dividendsExcess'] > 0)
        ? $state['dividendsExcess'] * $constants['dividendTaxRate']
        : 0;

    $tax13 = $state['income13'] > 0 ? calculateMonthlyTaxIRPF($state['income13']) : 0;

    $bestTaxOption = min($taxSimplified, $taxLegal);
    $isSimplifiedBetter = $taxSimplified < $taxLegal;

    $totalTaxLiability = $bestTaxOption + $dividendTax + $tax13;
    $finalResult = $totalTaxLiability - $state['taxPaid'];
    $isNegativeFinalResult = $finalResult < 0;
    $displayFinalResult = $isNegativeFinalResult ? abs($finalResult) : $finalResult;

    $totalIncome = ($state['incomeMonthly'] * 12) + ($state['incomeOther'] * 12) + $state['income13'] + $state['dividendsTotal'];
    $effectiveRate = $totalIncome > 0 ? ($totalTaxLiability / $totalIncome) * 100 : 0;

    $resultLabel = 'SEM SALDO';
    $resultClass = 'text-sm font-bold text-neutral-600 mt-1';
    $cardBorder = 'border-neutral-300';

    if ($finalResult > 0) {
        $resultLabel = 'IMPOSTO A PAGAR';
        $resultClass = 'text-sm font-bold text-red-600 mt-1';
        $cardBorder = 'border-red-500';
    } elseif ($isNegativeFinalResult) {
        $resultLabel = 'A RESTITUIR';
        $resultClass = 'text-sm font-bold text-green-600 mt-1';
        $cardBorder = 'border-green-500';
    }

    if ($totalIncome <= 0) {
        $recommendationText = 'Preencha os dados para ver qual regime compensa mais.';
    } elseif (abs($taxSimplified - $taxLegal) < 10) {
        $recommendationText = 'Resultados similares em ambos os regimes.';
    } elseif ($isSimplifiedBetter) {
        $difference = irpf_currency($taxLegal - $taxSimplified);
        $recommendationText = '<span class="text-brand-700 font-bold">Recomendação:</span> O Desconto Simplificado economiza <strong>' . $difference . '</strong>.';
    } else {
        $difference = irpf_currency($taxSimplified - $taxLegal);
        $recommendationText = '<span class="text-green-700 font-bold">Recomendação:</span> As Deduções Legais economizam <strong>' . $difference . '</strong>.';
    }

    $chartData = [
        'simplified' => round($taxSimplified + $dividendTax, 2),
        'legal' => round($taxLegal + $dividendTax, 2),
    ];

    $alerts = [];
    if ($educationWarning) {
        $alerts[] = 'Aplicamos o limite máximo permitido para as despesas com educação (R$ ' . number_format($constants['educationCap'], 2, ',', '.') . ').';
    }
    if ($state['highIncomeTrigger']) {
        $alerts[] = 'O gatilho do Imposto Mínimo está ativo. Esta versão apenas sinaliza a necessidade de revisar a alíquota efetiva mínima.';
    }
@endphp
<body class="flex flex-col min-h-screen">

    <header class="bg-white border-b border-neutral-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-16 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-sm">$</div>
                    <div>
                        <h1 class="text-xl font-bold text-neutral-800 tracking-tight">Simulador IRPF 2026</h1>
                        <p class="text-xs text-neutral-500 hidden sm:block">Baseado na Lei 15.270/2025 (MVP)</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center px-3 py-1 bg-neutral-100 rounded-full border border-neutral-200">
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span>
                        <span class="text-xs font-medium text-neutral-600">Salvo automaticamente</span>
                    </div>
                    <button type="button" onclick="resetApp()" class="text-sm text-brand-700 hover:text-brand-800 font-medium transition-colors">
                        Reiniciar
                    </button>
                </div>
            </div>
            <div class="border-t border-neutral-200">
                <nav class="flex gap-1">
                    <button onclick="switchView('calculator')" id="nav-calculator" class="nav-tab active px-6 py-3 text-sm font-medium text-brand-700 border-b-2 border-brand-600 transition-colors">
                        Calculadora
                    </button>
                    <button onclick="switchView('guide')" id="nav-guide" class="nav-tab px-6 py-3 text-sm font-medium text-neutral-500 border-b-2 border-transparent hover:text-brand-600 hover:border-brand-300 transition-colors">
                        Guia da Lei
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <div id="calculator-banner" class="view-section bg-brand-50 border-b border-brand-100">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <h2 class="text-lg font-semibold text-brand-900 mb-2">Entenda as Novas Regras (Lei 15.270/2025)</h2>
            <p class="text-brand-800 text-sm leading-relaxed max-w-4xl">
                Este simulador aplica as mudanças sancionadas em novembro de 2025, incluindo a <strong>nova faixa de isenção até R$ 5.000,00</strong>, a tributação exclusiva de <strong>dividendos acima de R$ 50 mil/mês</strong> e o <strong>Imposto Mínimo</strong> para grandes fortunas. Preencha os dados abaixo para ver o impacto no seu bolso em tempo real.
            </p>
        </div>
    </div>

    <div id="calculator-view" class="view-section">
    <main class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-7 xl:col-span-8">
            <form method="POST" action="{{ url()->current() }}" class="flex flex-col gap-6">
                @csrf
                <div class="w-full bg-neutral-200 rounded-full h-2.5 mb-2">
                    <div id="progressBar" class="bg-brand-600 h-2.5 rounded-full transition-all duration-500" style="width: 25%"></div>
                </div>

                <div id="step1" class="step-content active bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <div class="mb-6">
                        <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Etapa 1 de 4</span>
                        <h3 class="text-2xl font-bold text-neutral-800 mt-1">Perfil e Prioridades</h3>
                        <p class="text-neutral-500 text-sm mt-2">Vamos verificar se você se qualifica para isenções prioritárias antes de calcular a renda.</p>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-2" for="birthYear">Ano de Nascimento</label>
                            <input type="number" id="birthYear" name="birthYear" placeholder="Ex: 1980" value="{{ e(irpf_field_value($request, 'birthYear', $state['birthYear'], $submitted)) }}" class="w-full p-3 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-shadow outline-none">
                            <p class="text-xs text-neutral-400 mt-1">Usado para isenção extra de 65+ anos.</p>
                        </div>
                        <div class="flex items-start p-4 bg-neutral-50 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-100 transition-colors">
                            <div class="flex items-center h-5">
                                <input id="seriousIllness" name="seriousIllness" type="checkbox" class="w-4 h-4 text-brand-600 border-neutral-300 rounded focus:ring-brand-500" {{ $state['seriousIllness'] ? 'checked' : '' }}>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="seriousIllness" class="font-medium text-neutral-700">Possui moléstia grave prevista em lei?</label>
                                <p class="text-neutral-500 text-xs mt-1">Ex: Cardiopatia grave, alienação mental, neoplasia maligna. Zera imposto sobre aposentadorias.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end">
                        <button type="button" onclick="nextStep(2)" class="px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm flex items-center">
                            Próximo: Rendimentos <span class="ml-2">→</span>
                        </button>
                    </div>
                </div>

                <div id="step2" class="step-content bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <div class="mb-6">
                        <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Etapa 2 de 4</span>
                        <h3 class="text-2xl font-bold text-neutral-800 mt-1">Rendimentos (Lei 15.270)</h3>
                        <p class="text-neutral-500 text-sm mt-2">Informe suas fontes de renda. A nova lei isenta salários até R$ 5.000,00 e tributa dividendos altos.</p>
                    </div>
                    <div class="space-y-6">
                        <div class="p-4 border border-neutral-200 rounded-lg">
                            <h4 class="font-semibold text-neutral-800 mb-4 flex items-center gap-2">
                                <span>💼</span> Salários, Pró-Labore e Aposentadoria
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1" for="incomeMonthly">Renda Bruta Mensal Média</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                                        <input type="number" step="0.01" id="incomeMonthly" name="incomeMonthly" class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="0,00" value="{{ e(irpf_field_value($request, 'incomeMonthly', $state['incomeMonthly'], $submitted)) }}">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1" for="income13">13º Salário (Total)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                                        <input type="number" step="0.01" id="income13" name="income13" class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="0,00" value="{{ e(irpf_field_value($request, 'income13', $state['income13'], $submitted)) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border border-neutral-200 rounded-lg bg-brand-50/40">
                            <h4 class="font-semibold text-neutral-800 mb-4 flex items-center gap-2">
                                <span>📈</span> Lucros e Dividendos (Novidade)
                            </h4>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1" for="dividendsTotal">Total Recebido no Ano</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                                    <input type="number" step="0.01" id="dividendsTotal" name="dividendsTotal" class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="0,00" value="{{ e(irpf_field_value($request, 'dividendsTotal', $state['dividendsTotal'], $submitted)) }}">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="flex items-center text-sm text-neutral-700 mb-2">
                                    <input type="checkbox" id="hasExcessDividends" name="hasExcessDividends" class="mr-2 text-brand-600 rounded focus:ring-brand-500" {{ $state['hasExcessDividends'] ? 'checked' : '' }}>
                                    Recebi mais de R$ 50.000 de uma única fonte em algum mês?
                                </label>
                                <div id="dividendDetails" class="pl-6 mt-2 border-l-2 border-brand-200 {{ $state['hasExcessDividends'] ? '' : 'hidden' }}">
                                    <label class="block text-xs font-medium text-neutral-600 mb-1" for="dividendsExcess">Valor acumulado que excedeu o teto mensal de 50k:</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2.5 text-neutral-400 text-sm">R$</span>
                                        <input type="number" step="0.01" id="dividendsExcess" name="dividendsExcess" class="pl-8 w-full p-2 text-sm border border-neutral-300 rounded focus:ring-brand-500 outline-none" placeholder="Valor sujeito a 10%" value="{{ e(irpf_field_value($request, 'dividendsExcess', $state['dividendsExcess'], $submitted)) }}">
                                    </div>
                                    <p class="text-xs text-brand-600 mt-1">Este valor será tributado em 10% exclusivo na fonte.</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border border-neutral-200 rounded-lg">
                            <h4 class="font-semibold text-neutral-800 mb-4 flex items-center gap-2">
                                <span>🌍</span> Aluguéis e Exterior (Carnê-Leão)
                            </h4>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1" for="incomeOther">Renda Mensal Média</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                                    <input type="number" step="0.01" id="incomeOther" name="incomeOther" class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="0,00" value="{{ e(irpf_field_value($request, 'incomeOther', $state['incomeOther'], $submitted)) }}">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-neutral-700 mb-1" for="taxPaid">Imposto já pago (Carnê-Leão/Retido)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                                    <input type="number" step="0.01" id="taxPaid" name="taxPaid" class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="Total pago no ano" value="{{ e(irpf_field_value($request, 'taxPaid', $state['taxPaid'], $submitted)) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-between">
                        <button type="button" onclick="nextStep(1)" class="px-6 py-3 text-neutral-600 font-medium hover:text-neutral-900 transition-colors">← Voltar</button>
                        <button type="button" onclick="nextStep(3)" class="px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                            Próximo: Deduções →
                        </button>
                    </div>
                </div>

                <div id="step3" class="step-content bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <div class="mb-6">
                        <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Etapa 3 de 4</span>
                        <h3 class="text-2xl font-bold text-neutral-800 mt-1">Otimização Fiscal</h3>
                        <p class="text-neutral-500 text-sm mt-2">Informe seus gastos dedutíveis para reduzir a base de cálculo.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-1 md:col-span-2 bg-neutral-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-neutral-700 mb-2">Número de Dependentes</label>
                            <div class="flex items-center gap-4">
                                <button type="button" onclick="adjustCounter('dependents', -1)" class="w-10 h-10 rounded-full bg-white border border-neutral-300 flex items-center justify-center text-neutral-600 hover:bg-neutral-100 font-bold">-</button>
                                <input type="number" id="dependents" name="dependents" value="{{ e($request->input('dependents', $state['dependents'])) }}" readonly class="w-16 text-center bg-transparent font-bold text-xl text-brand-700">
                                <button type="button" onclick="adjustCounter('dependents', 1)" class="w-10 h-10 rounded-full bg-white border border-neutral-300 flex items-center justify-center text-neutral-600 hover:bg-neutral-100 font-bold">+</button>
                            </div>
                            <p class="text-xs text-neutral-400 mt-2">Valor da dedução por dependente: R$ 2.275,08/ano.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1" for="deductionHealth">Gastos com Saúde (Sem limite)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                                <input type="number" step="0.01" id="deductionHealth" name="deductionHealth" class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="0,00" value="{{ e(irpf_field_value($request, 'deductionHealth', $state['deductionHealth'], $submitted)) }}">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1" for="deductionEducation">Gastos com Educação</label>
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                                <input type="number" step="0.01" id="deductionEducation" name="deductionEducation" class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="0,00" value="{{ e(irpf_field_value($request, 'deductionEducation', $state['deductionEducation'], $submitted)) }}">
                            </div>
                            <p id="educationWarning" class="text-xs text-orange-600 mt-1 {{ $educationWarning ? '' : 'hidden' }}">Limite aplicado: R$ {{ number_format($constants['educationCap'], 2, ',', '.') }}.</p>
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-medium text-neutral-700 mb-1" for="deductionPGBL">Previdência Privada (PGBL)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-neutral-400">R$</span>
                                <input type="number" step="0.01" id="deductionPGBL" name="deductionPGBL" class="pl-10 w-full p-3 border border-neutral-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="0,00" value="{{ e(irpf_field_value($request, 'deductionPGBL', $state['deductionPGBL'], $submitted)) }}">
                            </div>
                            <p class="text-xs text-neutral-400 mt-1">Limitado a 12% da renda bruta tributável.</p>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-between">
                        <button type="button" onclick="nextStep(2)" class="px-6 py-3 text-neutral-600 font-medium hover:text-neutral-900 transition-colors">← Voltar</button>
                        <button type="button" onclick="nextStep(4)" class="px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                            Próximo: Patrimônio →
                        </button>
                    </div>
                </div>

                <div id="step4" class="step-content bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                    <div class="mb-6">
                        <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Etapa 4 de 4</span>
                        <h3 class="text-2xl font-bold text-neutral-800 mt-1">Imposto Mínimo (Grandes Fortunas)</h3>
                        <p class="text-neutral-500 text-sm mt-2">A Lei 15.270 instituiu um imposto mínimo para quem tem alta renda e patrimônio elevado.</p>
                    </div>
                    <div class="space-y-6">
                        <div class="p-6 bg-brand-50 rounded-lg border border-brand-100">
                            <h4 class="font-bold text-brand-900 mb-2">Gatilho de Imposto Mínimo</h4>
                            <p class="text-sm text-brand-800 mb-4">Se sua renda total (incluindo isentos) exceder R$ 600.000,00 no ano, uma alíquota mínima efetiva será aplicada.</p>
                            <label class="flex items-center text-sm text-neutral-700">
                                <input type="checkbox" id="highIncomeTrigger" name="highIncomeTrigger" class="mr-2 text-brand-600 rounded focus:ring-brand-500 w-5 h-5" {{ $state['highIncomeTrigger'] ? 'checked' : '' }}>
                                <span class="font-semibold">Minha renda total excede R$ 600.000/ano?</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-between">
                        <button type="button" onclick="nextStep(3)" class="px-6 py-3 text-neutral-600 font-medium hover:text-neutral-900 transition-colors">← Voltar</button>
                        <button type="submit" class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                            Calcular Resultado
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="lg:col-span-5 xl:col-span-4">
            <div class="sticky top-24 space-y-4">
                <div id="resultCard" class="bg-white p-6 rounded-xl shadow-md border-l-4 {{ $cardBorder }} transition-all">
                    @if (! $isNegativeFinalResult)
                        <h3 class="text-sm font-bold text-neutral-500 uppercase tracking-wider mb-1">Resultado Estimado</h3>
                    @endif
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-neutral-800" id="finalResultValue">{{ irpf_currency($displayFinalResult) }}</span>
                    </div>
                    <p id="resultStatus" class="{{ $resultClass }}">{{ $resultLabel }}</p>
                    <div class="mt-4 pt-4 border-t border-neutral-100 flex justify-between text-sm">
                        <span class="text-neutral-500">Alíquota Efetiva:</span>
                        <span id="effectiveRate" class="font-semibold text-neutral-800">{{ number_format($effectiveRate, 2, ',', '.') }}%</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-sm border border-neutral-200">
                    <h4 class="text-sm font-semibold text-neutral-800 mb-3">Comparativo de Regimes</h4>
                    <div class="chart-container h-48">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                    <p id="recommendationText" class="text-xs text-center mt-2 text-neutral-500 bg-neutral-50 p-2 rounded">{!! $recommendationText !!}</p>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-sm border border-neutral-200 dashboard-scroll max-h-[40vh] overflow-y-auto">
                    <h4 class="text-sm font-semibold text-neutral-800 mb-3">Detalhamento do Cálculo</h4>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Renda Bruta Total:</span>
                            <span class="font-medium" id="displayGrossIncome">{{ irpf_currency($grossTaxable) }}</span>
                        </div>
                        <div class="flex justify-between text-brand-700">
                            <span class="text-neutral-500">Desconto Simplificado:</span>
                            <span class="font-medium" id="displaySimplifiedDisc">- {{ irpf_currency($simplifiedDiscount) }}</span>
                        </div>
                        <div class="flex justify-between text-green-600">
                            <span class="text-neutral-500">Total Deduções Legais:</span>
                            <span class="font-medium" id="displayLegalDed">- {{ irpf_currency($totalDeductions) }}</span>
                        </div>
                        <div class="flex justify-between text-brand-600 border-t border-neutral-100 pt-2">
                            <span class="text-neutral-500">Imposto s/ Dividendos:</span>
                            <span class="font-medium" id="displayDivTax">+ {{ irpf_currency($dividendTax) }}</span>
                        </div>
                        <div class="flex justify-between text-red-600">
                            <span class="text-neutral-500">Imposto Devido (Melhor):</span>
                            <span class="font-medium" id="displayTaxDue">{{ irpf_currency($totalTaxLiability) }}</span>
                        </div>
                        <div class="flex justify-between text-neutral-400 text-xs">
                            <span>(-) Imposto Já Pago:</span>
                            <span id="displayTaxPaid">- {{ irpf_currency($state['taxPaid']) }}</span>
                        </div>
                    </div>
                </div>

                <div id="alertsContainer" class="space-y-2">
                    @foreach($alerts as $alert)
                        <div class="bg-brand-50 border border-brand-100 text-brand-900 text-sm p-3 rounded-lg">
                            {{ $alert }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </main>
    </div>

    <div id="guide-view" class="view-section hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <aside class="lg:col-span-1">
                    <div class="sticky top-24 bg-white p-4 rounded-xl shadow-sm border border-neutral-200">
                        <h3 class="text-sm font-bold text-neutral-700 uppercase tracking-wider mb-4">Navegação</h3>
                        <nav class="space-y-2">
                            <a href="#visao-geral" onclick="scrollToSection('visao-geral'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Visão Geral</a>
                            <a href="#artigo-6a" onclick="scrollToSection('artigo-6a'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Art. 6-A / IR na Fonte</a>
                            <a href="#irpf-minimo" onclick="scrollToSection('irpf-minimo'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">IRPF Mínimo</a>
                            <a href="#travas" onclick="scrollToSection('travas'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Travas (Art. 16-B)</a>
                            <a href="#distribuicao-exterior" onclick="scrollToSection('distribuicao-exterior'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Distribuição no Exterior</a>
                            <a href="#estrategias" onclick="scrollToSection('estrategias'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Estratégias</a>
                            <a href="#riscos" onclick="scrollToSection('riscos'); return false;" class="nav-link block px-3 py-2 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors">Riscos</a>
                            <a href="#calculadora-link" onclick="switchView('calculator'); return false;" class="nav-link block px-3 py-2 text-sm text-brand-600 hover:bg-brand-50 hover:text-brand-700 rounded transition-colors font-medium">→ Calculadora</a>
                        </nav>
                    </div>
                </aside>
                <div class="lg:col-span-3 space-y-8">
                    <section id="visao-geral" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                        <h2 class="text-2xl font-bold text-neutral-800 mb-4">Visão Geral do Sistema</h2>
                        <div class="prose prose-sm max-w-none">
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                A <strong>Lei nº 15.270, de 26 de novembro de 2025</strong>, originada do <strong>Projeto de Lei nº 1.087/2025</strong>, introduziu mudanças significativas na tributação do Imposto de Renda da Pessoa Física (IRPF) no Brasil, com vigência a partir de 1º de janeiro de 2026.
                            </p>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Objetivos</h3>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                                <li>Aumentar a progressividade do sistema tributário brasileiro</li>
                                <li>Aliviar a carga tributária para rendas mais baixas</li>
                                <li>Aumentar a tributação sobre rendas mais altas e grandes fortunas</li>
                                <li>Equiparar a tributação de dividendos entre residentes e não residentes</li>
                            </ul>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Abrangência</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                A lei afeta todos os contribuintes pessoa física residentes no Brasil, com impactos diferenciados conforme a faixa de renda:
                            </p>
                            <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 mb-4">
                                <ul class="space-y-2 text-neutral-700">
                                    <li><strong>Rendimentos até R$ 5.000/mês:</strong> Isenção integral do IRPF</li>
                                    <li><strong>Rendimentos entre R$ 5.000,01 e R$ 7.350/mês:</strong> Redução progressiva do imposto</li>
                                    <li><strong>Dividendos acima de R$ 50.000/mês:</strong> Tributação exclusiva de 10% na fonte</li>
                                    <li><strong>Rendimentos anuais acima de R$ 600.000:</strong> Sujeitos ao Imposto de Renda Mínimo</li>
                                </ul>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Principais Mudanças</h3>
                            <ol class="list-decimal list-inside text-neutral-700 space-y-2">
                                <li><strong>Ampliação da faixa de isenção:</strong> De R$ 2.112,00 para R$ 5.000,00 mensais</li>
                                <li><strong>Nova tabela progressiva:</strong> 5 faixas com alíquotas de 0% a 27,5%</li>
                                <li><strong>Tributação de dividendos:</strong> Retenção na fonte de 10% para valores acima de R$ 50.000/mês</li>
                                <li><strong>Imposto Mínimo:</strong> Tributação adicional progressiva para altas rendas</li>
                                <li><strong>Trava de segurança:</strong> Mecanismo para evitar tributação excessiva (Art. 16-B)</li>
                            </ol>
                        </div>
                    </section>

                    <section id="artigo-6a" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                        <h2 class="text-2xl font-bold text-neutral-800 mb-4">Artigo 6-A / Imposto de Renda na Fonte</h2>
                        <div class="prose prose-sm max-w-none">
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Mecânica de Funcionamento</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                O <strong>Artigo 6-A</strong> da Lei 15.270/2025 estabelece que, a partir de 1º de janeiro de 2026, <strong>lucros e dividendos distribuídos por pessoa jurídica a pessoa física</strong> que excedam <strong>R$ 50.000,00 mensais</strong> estarão sujeitos à <strong>retenção na fonte de 10% de IRPF</strong>.
                            </p>
                            <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 mb-4">
                                <p class="text-sm text-neutral-700 mb-2"><strong>Características importantes:</strong></p>
                                <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                                    <li>A retenção é <strong>definitiva</strong> e não permite deduções na base de cálculo</li>
                                    <li>O limite de R$ 50.000 é aplicado <strong>por pessoa jurídica</strong> e <strong>por mês</strong></li>
                                    <li>Apenas o valor que excede R$ 50.000 é tributado</li>
                                    <li>Não há compensação na declaração anual de ajuste</li>
                                </ul>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Cenários Práticos</h3>
                            <div class="space-y-4 mb-4">
                                <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                                    <h4 class="font-semibold text-neutral-800 mb-2">Exemplo 1: Dividendos dentro do limite</h4>
                                    <p class="text-sm text-neutral-700 mb-2">Um acionista recebe R$ 45.000,00 de dividendos em um mês de uma única empresa.</p>
                                    <p class="text-sm text-neutral-600"><strong>Resultado:</strong> Não há retenção na fonte, pois o valor está abaixo do limite de R$ 50.000,00.</p>
                                </div>
                                <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                                    <h4 class="font-semibold text-neutral-800 mb-2">Exemplo 2: Dividendos acima do limite</h4>
                                    <p class="text-sm text-neutral-700 mb-2">Um acionista recebe R$ 60.000,00 de dividendos em um mês de uma única empresa.</p>
                                    <p class="text-sm text-neutral-700 mb-2"><strong>Cálculo:</strong></p>
                                    <ul class="text-sm text-neutral-600 list-disc list-inside ml-4">
                                        <li>Valor excedente: R$ 60.000 - R$ 50.000 = R$ 10.000</li>
                                        <li>Imposto retido: R$ 10.000 × 10% = <strong>R$ 1.000,00</strong></li>
                                    </ul>
                                </div>
                                <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                                    <h4 class="font-semibold text-neutral-800 mb-2">Exemplo 3: Múltiplas empresas</h4>
                                    <p class="text-sm text-neutral-700 mb-2">Um investidor recebe R$ 40.000 de cada uma de 3 empresas diferentes no mesmo mês (total: R$ 120.000).</p>
                                    <p class="text-sm text-neutral-600"><strong>Resultado:</strong> Não há retenção, pois cada pagamento individual está abaixo de R$ 50.000,00. O limite é aplicado por pessoa jurídica, não pelo total recebido.</p>
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Regime de Transição (Inciso 3º)</h3>
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200 mb-4">
                                <p class="text-sm text-neutral-700 mb-2"><strong>Isenção temporária:</strong></p>
                                <p class="text-sm text-neutral-700">
                                    Lucros e dividendos referentes a <strong>resultados apurados até 31 de dezembro de 2025</strong>, cuja distribuição tenha sido <strong>deliberada e aprovada até essa data</strong>, permanecem isentos de tributação, mesmo que o pagamento ocorra após 1º de janeiro de 2026, desde que realizado <strong>até 31 de dezembro de 2028</strong>.
                                </p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <p class="text-sm text-neutral-700"><strong>⚠️ Atenção:</strong> Para aproveitar o regime de transição, é necessário que tanto a apuração do resultado quanto a deliberação da distribuição tenham ocorrido até 31/12/2025.</p>
                            </div>
                        </div>
                    </section>

                    <section id="irpf-minimo" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                        <h2 class="text-2xl font-bold text-neutral-800 mb-4">Imposto de Renda Mínimo (IRPF Min.)</h2>
                        <div class="prose prose-sm max-w-none">
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Funcionamento</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                O <strong>Imposto de Renda Mínimo</strong> é uma tributação adicional progressiva aplicada a contribuintes pessoa física com <strong>rendimentos anuais superiores a R$ 600.000,00</strong>. O objetivo é garantir que contribuintes de alta renda tenham uma alíquota efetiva mínima, mesmo considerando rendimentos isentos ou tributados exclusivamente na fonte.
                            </p>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Base de Cálculo</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                A base de cálculo do IRPF Min. inclui:
                            </p>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                                <li>Todos os rendimentos tributáveis</li>
                                <li>Rendimentos isentos (exceto os especificados em lei)</li>
                                <li>Rendimentos tributados exclusivamente na fonte</li>
                                <li>Lucros e dividendos recebidos</li>
                            </ul>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Tabela de Alíquotas Progressivas</h3>
                            <div class="overflow-x-auto mb-4">
                                <table class="min-w-full border border-neutral-300 rounded-lg">
                                    <thead class="bg-brand-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-neutral-800 border-b border-neutral-300">Faixa de Renda Anual</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-neutral-800 border-b border-neutral-300">Alíquota</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm text-neutral-700">
                                        <tr class="border-b border-neutral-200">
                                            <td class="px-4 py-3">Até R$ 600.000,00</td>
                                            <td class="px-4 py-3 text-center">0%</td>
                                        </tr>
                                        <tr class="border-b border-neutral-200 bg-neutral-50">
                                            <td class="px-4 py-3">De R$ 600.000,01 a R$ 800.000,00</td>
                                            <td class="px-4 py-3 text-center font-semibold">5%</td>
                                        </tr>
                                        <tr class="border-b border-neutral-200">
                                            <td class="px-4 py-3">De R$ 800.000,01 a R$ 1.000.000,00</td>
                                            <td class="px-4 py-3 text-center font-semibold">7,5%</td>
                                        </tr>
                                        <tr class="border-b border-neutral-200 bg-neutral-50">
                                            <td class="px-4 py-3">De R$ 1.000.000,01 a R$ 1.200.000,00</td>
                                            <td class="px-4 py-3 text-center font-semibold">9%</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3">Acima de R$ 1.200.000,00</td>
                                            <td class="px-4 py-3 text-center font-semibold text-brand-700">10%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Exemplo de Cálculo</h3>
                            <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200 mb-4">
                                <p class="text-sm text-neutral-700 mb-3"><strong>Cenário:</strong> Contribuinte com rendimentos anuais de R$ 900.000,00</p>
                                <div class="space-y-2 text-sm text-neutral-700">
                                    <p><strong>Cálculo progressivo:</strong></p>
                                    <ul class="list-disc list-inside ml-4 space-y-1">
                                        <li>1ª faixa (R$ 600.000,01 a R$ 800.000): R$ 200.000 × 5% = <strong>R$ 10.000,00</strong></li>
                                        <li>2ª faixa (R$ 800.000,01 a R$ 900.000): R$ 100.000 × 7,5% = <strong>R$ 7.500,00</strong></li>
                                    </ul>
                                    <p class="mt-2 font-semibold">IRPF Min. Total: <strong>R$ 17.500,00</strong></p>
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Deduções Permitidas</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                Podem ser deduzidos da base de cálculo do IRPF Min.:
                            </p>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2">
                                <li>O imposto devido na Declaração de Ajuste Anual (IRPF normal)</li>
                                <li>O imposto retido na fonte sobre rendimentos incluídos na base de cálculo</li>
                                <li>O imposto pago sobre rendas auferidas no exterior (respeitando acordos internacionais)</li>
                            </ul>
                        </div>
                    </section>

                    <section id="travas" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                        <h2 class="text-2xl font-bold text-neutral-800 mb-4">Travas - Artigo 16-B</h2>
                        <div class="prose prose-sm max-w-none">
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Trava de Segurança para Tributação Excessiva</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                O <strong>Artigo 16-B</strong> da Lei 15.270/2025 estabelece um mecanismo de segurança para evitar que a carga tributária total sobre lucros e dividendos ultrapasse limites considerados excessivos, garantindo justiça fiscal e evitando bitributação.
                            </p>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Mecanismo de Funcionamento</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                A trava é acionada quando a <strong>soma das alíquotas efetivas</strong> de tributação ultrapassa os limites estabelecidos:
                            </p>
                            <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 mb-4">
                                <p class="text-sm text-neutral-700 mb-2"><strong>Limites de alíquota efetiva total:</strong></p>
                                <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                                    <li><strong>34%</strong> para empresas em geral</li>
                                    <li><strong>40%</strong> para instituições financeiras</li>
                                    <li><strong>45%</strong> para atividades específicas regulamentadas</li>
                                </ul>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Cálculo da Trava</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                A trava considera:
                            </p>
                            <ol class="list-decimal list-inside text-neutral-700 space-y-2 mb-4">
                                <li><strong>Alíquota efetiva da pessoa jurídica:</strong> Soma do IRPJ e CSLL sobre o lucro</li>
                                <li><strong>Alíquota efetiva do IRPF:</strong> Imposto devido pelo beneficiário pessoa física</li>
                                <li><strong>Verificação:</strong> Se a soma ultrapassar o limite, aplica-se um redutor no IRPF</li>
                            </ol>
                            <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200 mb-4">
                                <h4 class="font-semibold text-neutral-800 mb-2">Exemplo Prático</h4>
                                <p class="text-sm text-neutral-700 mb-2">Empresa com alíquota efetiva de 30% (IRPJ + CSLL) distribui dividendos a pessoa física.</p>
                                <p class="text-sm text-neutral-700 mb-2">Sem a trava, o IRPF sobre dividendos seria de 10%, totalizando 40%.</p>
                                <p class="text-sm text-neutral-600"><strong>Com a trava:</strong> Se o limite for 34%, o IRPF é reduzido para 4%, mantendo o total em 34%.</p>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Benefícios</h3>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2">
                                <li>Evita tributação excessiva sobre a mesma base de cálculo</li>
                                <li>Garante previsibilidade e justiça fiscal</li>
                                <li>Protege pequenos e médios investidores</li>
                                <li>Mantém a competitividade do sistema tributário brasileiro</li>
                            </ul>
                        </div>
                    </section>

                    <section id="distribuicao-exterior" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                        <h2 class="text-2xl font-bold text-neutral-800 mb-4">Implicações com a Distribuição no Exterior</h2>
                        <div class="prose prose-sm max-w-none">
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Tributação de Não Residentes</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                A Lei 15.270/2025 estabelece que <strong>lucros e dividendos remetidos ao exterior</strong>, tanto para pessoas físicas quanto jurídicas não residentes, estarão sujeitos à <strong>retenção na fonte de 10% de IRPF</strong>, <strong>independentemente do valor</strong>.
                            </p>
                            <div class="bg-red-50 p-4 rounded-lg border border-red-200 mb-4">
                                <p class="text-sm text-neutral-700"><strong>⚠️ Diferença importante:</strong> Para não residentes, não há limite de R$ 50.000/mês. Qualquer valor remetido ao exterior está sujeito à retenção de 10%.</p>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Impactos em Investimentos Estrangeiros</h3>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                                <li><strong>Redução da rentabilidade líquida:</strong> Investidores estrangeiros terão retorno líquido menor</li>
                                <li><strong>Possível redução de investimentos:</strong> A nova tributação pode desestimular investimentos estrangeiros no Brasil</li>
                                <li><strong>Impacto em fundos internacionais:</strong> Fundos de investimento estrangeiros podem revisar suas estratégias</li>
                            </ul>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Acordos Internacionais</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                O Brasil possui <strong>acordos para evitar bitributação</strong> com diversos países. Nestes casos:
                            </p>
                            <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 mb-4">
                                <ul class="list-disc list-inside text-sm text-neutral-700 space-y-2">
                                    <li>A tributação pode ser reduzida conforme o acordo específico</li>
                                    <li>É possível creditar o imposto pago no Brasil contra o imposto devido no país de residência</li>
                                    <li>Recomenda-se consultar o acordo específico entre Brasil e o país do beneficiário</li>
                                </ul>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Planejamento Tributário</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                Para empresas com investidores estrangeiros, é importante:
                            </p>
                            <ol class="list-decimal list-inside text-neutral-700 space-y-2">
                                <li>Verificar a existência de acordos de bitributação</li>
                                <li>Calcular o impacto líquido da nova tributação</li>
                                <li>Considerar estruturas alternativas de investimento</li>
                                <li>Comunicar claramente aos investidores sobre as mudanças</li>
                            </ol>
                        </div>
                    </section>

                    <section id="estrategias" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                        <h2 class="text-2xl font-bold text-neutral-800 mb-4">Estratégias</h2>
                        <div class="prose prose-sm max-w-none">
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Planejamento Tributário</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                Diante das mudanças introduzidas pela Lei 15.270/2025, é fundamental revisar estratégias de planejamento tributário para otimizar a carga fiscal.
                            </p>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">1. Planejamento de Distribuição de Dividendos</h3>
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200 mb-4">
                                <p class="text-sm text-neutral-700 mb-2"><strong>Estratégia:</strong> Antecipar distribuições</p>
                                <p class="text-sm text-neutral-700">
                                    Empresas podem considerar <strong>antecipar a distribuição de dividendos</strong> referentes a lucros apurados até 31 de dezembro de 2025, aproveitando a isenção prevista no regime de transição. Distribuições aprovadas até essa data e pagas até 2028 permanecem isentas.
                                </p>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">2. Reestruturação de Remuneração</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                Para sócios e administradores, pode ser vantajoso revisar a composição entre:
                            </p>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                                <li><strong>Pró-labore:</strong> Sujeito à tabela progressiva (0% a 27,5%)</li>
                                <li><strong>Dividendos:</strong> Isentos até R$ 50.000/mês, depois 10% na fonte</li>
                            </ul>
                            <div class="bg-neutral-50 p-4 rounded-lg border border-neutral-200 mb-4">
                                <p class="text-sm text-neutral-700"><strong>⚠️ Atenção:</strong> A escolha entre pró-labore e dividendos deve considerar não apenas a carga tributária, mas também questões trabalhistas, previdenciárias e de governança.</p>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">3. Otimização de Deduções</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                Maximizar o uso de deduções legais pode reduzir significativamente a base de cálculo:
                            </p>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                                <li><strong>Dependentes:</strong> R$ 2.275,08 por dependente</li>
                                <li><strong>Saúde:</strong> Sem limite de dedução</li>
                                <li><strong>Educação:</strong> Até R$ 3.561,50 por dependente</li>
                                <li><strong>PGBL:</strong> Até 12% da renda bruta tributável</li>
                            </ul>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">4. Estratégias para Altas Rendas</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                Para contribuintes sujeitos ao IRPF Min. (renda acima de R$ 600.000/ano):
                            </p>
                            <ol class="list-decimal list-inside text-neutral-700 space-y-2">
                                <li>Monitorar a alíquota efetiva total para garantir que não ultrapasse os limites</li>
                                <li>Considerar o uso da trava de segurança (Art. 16-B) quando aplicável</li>
                                <li>Avaliar a distribuição de rendimentos ao longo do ano para otimizar a carga tributária</li>
                                <li>Consultar especialistas em planejamento tributário para estruturas mais complexas</li>
                            </ol>
                        </div>
                    </section>

                    <section id="riscos" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                        <h2 class="text-2xl font-bold text-neutral-800 mb-4">Riscos</h2>
                        <div class="prose prose-sm max-w-none">
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Complexidade na Apuração</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                As novas regras aumentam significativamente a complexidade na apuração do imposto devido, exigindo:
                            </p>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                                <li>Controle mensal detalhado de dividendos recebidos por fonte pagadora</li>
                                <li>Cálculo preciso do IRPF Min. considerando todas as faixas progressivas</li>
                                <li>Verificação da aplicação das travas de segurança</li>
                                <li>Monitoramento do regime de transição para dividendos</li>
                            </ul>
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-4">
                                <p class="text-sm text-neutral-700"><strong>⚠️ Risco:</strong> Erros na apuração podem resultar em multas, juros e possíveis autuações fiscais.</p>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Possíveis Controvérsias Jurídicas</h3>
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                Mudanças na legislação podem gerar interpretações divergentes e litígios, especialmente em relação a:
                            </p>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                                <li><strong>Aplicação do IRPF Min.:</strong> Definição precisa da base de cálculo e rendimentos incluídos</li>
                                <li><strong>Regime de transição:</strong> Interpretação sobre o que constitui "deliberação" e "aprovação" até 31/12/2025</li>
                                <li><strong>Trava de segurança:</strong> Cálculo da alíquota efetiva e aplicação dos redutores</li>
                                <li><strong>Distribuição no exterior:</strong> Interação com acordos internacionais de bitributação</li>
                            </ul>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Penalidades e Implicações Legais</h3>
                            <div class="bg-red-50 p-4 rounded-lg border border-red-200 mb-4">
                                <p class="text-sm text-neutral-700 mb-2"><strong>Multas e juros:</strong></p>
                                <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                                    <li>Multa por atraso no recolhimento: até 20% do valor devido</li>
                                    <li>Juros de mora: Selic acumulada</li>
                                    <li>Multa por omissão ou erro: 75% a 150% do imposto devido</li>
                                </ul>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Riscos Operacionais</h3>
                            <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-4">
                                <li><strong>Falta de sistemas adequados:</strong> Empresas podem não ter sistemas atualizados para calcular corretamente as novas obrigações</li>
                                <li><strong>Treinamento insuficiente:</strong> Equipes contábeis e fiscais podem não estar preparadas para as mudanças</li>
                                <li><strong>Prazos apertados:</strong> A implementação a partir de 2026 pode não dar tempo suficiente para adaptação</li>
                            </ul>
                            <h3 class="text-lg font-semibold text-neutral-800 mt-6 mb-3">Recomendações para Mitigação</h3>
                            <ol class="list-decimal list-inside text-neutral-700 space-y-2">
                                <li>Investir em sistemas de controle e apuração adequados</li>
                                <li>Treinar equipes sobre as novas regras</li>
                                <li>Consultar especialistas em direito tributário</li>
                                <li>Manter documentação detalhada de todas as operações</li>
                                <li>Realizar revisões periódicas dos cálculos</li>
                                <li>Considerar seguro de responsabilidade fiscal</li>
                            </ol>
                        </div>
                    </section>

                    <section id="calculadora-link" class="doc-section bg-white p-6 rounded-xl shadow-sm border border-neutral-200">
                        <h2 class="text-2xl font-bold text-neutral-800 mb-4">Calculadora</h2>
                        <div class="prose prose-sm max-w-none">
                            <p class="text-neutral-700 leading-relaxed mb-4">
                                Utilize nossa <strong>calculadora interativa</strong> para simular o impacto das mudanças introduzidas pela Lei 15.270/2025 no seu caso específico.
                            </p>
                            <div class="bg-brand-50 p-6 rounded-lg border border-brand-200 text-center">
                                <p class="text-neutral-700 mb-4">A calculadora permite:</p>
                                <ul class="list-disc list-inside text-neutral-700 space-y-2 mb-6 text-left max-w-md mx-auto">
                                    <li>Calcular o imposto devido considerando as novas faixas</li>
                                    <li>Simular a tributação de dividendos acima de R$ 50.000/mês</li>
                                    <li>Comparar o regime simplificado com deduções legais</li>
                                    <li>Estimar o impacto do IRPF Mínimo</li>
                                </ul>
                                <button onclick="switchView('calculator')" class="px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                                    Acessar Calculadora →
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-white border-t border-neutral-200 mt-auto">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-xs text-neutral-500 text-center md:text-left">
                &copy; 2025 Ferramenta de Simulação. <strong>Baseado na Lei 15.270/2025</strong>.<br>
                Este simulador é educativo e não substitui o Programa Gerador da Declaração (PGD) da Receita Federal.
            </p>
            <div class="flex gap-4">
                <button onclick="window.print()" type="button" class="text-xs font-semibold text-brand-600 hover:text-brand-800">
                    Imprimir Relatório
                </button>
            </div>
        </div>
    </footer>

    <script>
        const chartPayload = @json($chartData);
        const hasSubmission = @json($submitted);
        let comparisonChart = null;
        const CURRENT_YEAR = 2026;
        const IRPF_CONSTANTS = {
            deductionPerDependent: 2275.08,
            educationCap: 3561.50,
            simplifiedRate: 0.20,
            simplifiedCap: 16754.34,
            dividendTaxRate: 0.10,
        };

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

        function calculateProgressiveTaxIRPF(annualBase) {
            if (!Number.isFinite(annualBase) || annualBase <= 0) {
                return 0;
            }

            const monthlyBase = annualBase / 12;
            let monthlyTax = 0;

            if (monthlyBase <= 5000) {
                monthlyTax = 0;
            } else if (monthlyBase <= 7350) {
                monthlyTax = (monthlyBase * 0.075) - 375;
            } else if (monthlyBase <= 9250) {
                monthlyTax = (monthlyBase * 0.15) - 926.25;
            } else if (monthlyBase <= 12000) {
                monthlyTax = (monthlyBase * 0.225) - 1620;
            } else {
                monthlyTax = (monthlyBase * 0.275) - 2220;
            }

            return Math.max(0, monthlyTax * 12);
        }

        function calculateMonthlyTaxIRPF(monthlyBase) {
            if (!Number.isFinite(monthlyBase) || monthlyBase <= 0) {
                return 0;
            }

            if (monthlyBase <= 5000) {
                return 0;
            }
            if (monthlyBase <= 7350) {
                return Math.max(0, (monthlyBase * 0.075) - 375);
            }
            if (monthlyBase <= 9250) {
                return Math.max(0, (monthlyBase * 0.15) - 926.25);
            }
            if (monthlyBase <= 12000) {
                return Math.max(0, (monthlyBase * 0.225) - 1620);
            }
            return Math.max(0, (monthlyBase * 0.275) - 2220);
        }

        function collectStateFromForm() {
            const numberValue = (id) => {
                const el = document.getElementById(id);
                return el ? parseInputNumber(el.value) : 0;
            };
            const checkboxValue = (id) => {
                const el = document.getElementById(id);
                return el ? el.checked : false;
            };
            const birthYearInput = document.getElementById('birthYear');
            const birthYear = birthYearInput && birthYearInput.value !== '' ? parseInt(birthYearInput.value, 10) : null;

            return {
                birthYear: Number.isFinite(birthYear) ? birthYear : null,
                seriousIllness: checkboxValue('seriousIllness'),
                incomeMonthly: numberValue('incomeMonthly'),
                income13: numberValue('income13'),
                dividendsTotal: numberValue('dividendsTotal'),
                hasExcessDividends: checkboxValue('hasExcessDividends'),
                dividendsExcess: numberValue('dividendsExcess'),
                incomeOther: numberValue('incomeOther'),
                taxPaid: numberValue('taxPaid'),
                dependents: (() => {
                    const input = document.getElementById('dependents');
                    const val = input && input.value !== '' ? parseInt(input.value, 10) : 0;
                    return Number.isFinite(val) && val > 0 ? val : 0;
                })(),
                deductionHealth: numberValue('deductionHealth'),
                deductionEducation: numberValue('deductionEducation'),
                deductionPGBL: numberValue('deductionPGBL'),
                highIncomeTrigger: checkboxValue('highIncomeTrigger'),
            };
        }

        function computeIrpfMetrics(state) {
            let grossTaxable = (state.incomeMonthly * 12) + (state.incomeOther * 12);
            const age = state.birthYear ? (CURRENT_YEAR - state.birthYear) : 0;

            if (age >= 65) {
                grossTaxable = Math.max(0, grossTaxable - 24000);
            }

            if (state.seriousIllness) {
                grossTaxable = state.incomeOther * 12;
            }

            const simplifiedDiscount = Math.min(grossTaxable * IRPF_CONSTANTS.simplifiedRate, IRPF_CONSTANTS.simplifiedCap);
            const baseSimplified = grossTaxable - simplifiedDiscount;
            const taxSimplified = calculateProgressiveTaxIRPF(Math.max(0, baseSimplified));

            let educationWarning = false;
            let educationDeduction = state.deductionEducation;
            if (educationDeduction > IRPF_CONSTANTS.educationCap) {
                educationWarning = true;
                educationDeduction = IRPF_CONSTANTS.educationCap;
            }

            const pgblCap = Math.max(0, grossTaxable * 0.12);
            const pgblDeduction = Math.min(state.deductionPGBL, pgblCap);

            const totalDeductions = (state.dependents * IRPF_CONSTANTS.deductionPerDependent)
                + state.deductionHealth
                + educationDeduction
                + pgblDeduction;

            const baseLegal = Math.max(0, grossTaxable - totalDeductions);
            const taxLegal = calculateProgressiveTaxIRPF(baseLegal);

            const dividendTax = (state.hasExcessDividends && state.dividendsExcess > 0)
                ? state.dividendsExcess * IRPF_CONSTANTS.dividendTaxRate
                : 0;

            const tax13 = state.income13 > 0 ? calculateMonthlyTaxIRPF(state.income13) : 0;

            const bestTaxOption = Math.min(taxSimplified, taxLegal);
            const isSimplifiedBetter = taxSimplified < taxLegal;
            const totalTaxLiability = bestTaxOption + dividendTax + tax13;
            const finalResult = totalTaxLiability - state.taxPaid;
            const isNegativeFinalResult = finalResult < 0;
            const displayFinalResult = isNegativeFinalResult ? Math.abs(finalResult) : finalResult;

            const totalIncome = (state.incomeMonthly * 12)
                + (state.incomeOther * 12)
                + state.income13
                + state.dividendsTotal;
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
            };
        }

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
            setText('displayTaxPaid', `- ${formatCurrency(state.taxPaid)}`);

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
            chartPayload.simplified = metrics.chartData.simplified;
            chartPayload.legal = metrics.chartData.legal;

            if (comparisonChart) {
                comparisonChart.data.datasets[0].data = [chartPayload.simplified, chartPayload.legal];
                comparisonChart.update();
            }
        }

        function recalculateSummary() {
            const state = collectStateFromForm();
            const metrics = computeIrpfMetrics(state);
            updateSummaryUI(state, metrics);
            updateChartData(metrics);
        }

        function setupRealtimeCalculation() {
            const inputs = document.querySelectorAll('input[type="number"], input[type="checkbox"]');
            inputs.forEach(input => {
                const eventName = input.type === 'checkbox' ? 'change' : 'input';
                input.addEventListener(eventName, recalculateSummary);
            });
            recalculateSummary();
        }

        function resetApp() {
            if (confirm('Deseja reiniciar o simulador?')) {
                window.location = window.location.pathname;
            }
        }

        function nextStep(stepNumber) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            const target = document.getElementById(`step${stepNumber}`);
            if (target) {
                target.classList.add('active');
            }
            const progress = Math.min(100, Math.max(25, stepNumber * 25));
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

        function toggleDividendDetails(el) {
            const details = document.getElementById('dividendDetails');
            if (!details || !el) return;
            if (el.checked) {
                details.classList.remove('hidden');
            } else {
                details.classList.add('hidden');
                const input = document.getElementById('dividendsExcess');
                if (input) {
                    input.value = '';
                }
            }
            recalculateSummary();
        }

        function initChart() {
            const ctx = document.getElementById('comparisonChart');
            if (!ctx) return;
            comparisonChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Simplificado', 'Deduções Legais'],
                    datasets: [{
                        label: 'Imposto Anual Estimado',
                        data: [chartPayload.simplified, chartPayload.legal],
                        backgroundColor: [
                            'rgba(37, 99, 235, 0.7)',
                            'rgba(99, 102, 241, 0.7)'
                        ],
                        borderColor: [
                            'rgba(37, 99, 235, 1)',
                            'rgba(99, 102, 241, 1)'
                        ],
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { display: false }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
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

        function switchView(view) {
            const calculatorView = document.getElementById('calculator-view');
            const calculatorBanner = document.getElementById('calculator-banner');
            const guideView = document.getElementById('guide-view');
            const navCalculator = document.getElementById('nav-calculator');
            const navGuide = document.getElementById('nav-guide');

            if (view === 'calculator') {
                calculatorView.classList.remove('hidden');
                if (calculatorBanner) calculatorBanner.classList.remove('hidden');
                guideView.classList.add('hidden');
                navCalculator.classList.add('active');
                navCalculator.classList.remove('text-neutral-500', 'border-transparent');
                navCalculator.classList.add('text-brand-700', 'border-brand-600');
                navGuide.classList.remove('active', 'text-brand-700', 'border-brand-600');
                navGuide.classList.add('text-neutral-500', 'border-transparent');
            } else if (view === 'guide') {
                calculatorView.classList.add('hidden');
                if (calculatorBanner) calculatorBanner.classList.add('hidden');
                guideView.classList.remove('hidden');
                navGuide.classList.add('active');
                navGuide.classList.remove('text-neutral-500', 'border-transparent');
                navGuide.classList.add('text-brand-700', 'border-brand-600');
                navCalculator.classList.remove('active', 'text-brand-700', 'border-brand-600');
                navCalculator.classList.add('text-neutral-500', 'border-transparent');
            }
        }

        function scrollToSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                const headerOffset = 120;
                const elementPosition = section.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                // Atualizar navegação ativa
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('bg-brand-50', 'text-brand-700', 'font-medium');
                    link.classList.add('text-neutral-600');
                });
                const activeLink = document.querySelector(`a[href="#${sectionId}"]`);
                if (activeLink) {
                    activeLink.classList.add('bg-brand-50', 'text-brand-700', 'font-medium');
                    activeLink.classList.remove('text-neutral-600');
                }
            }
        }

        // Observador para destacar seção ativa durante scroll
        function setupScrollObserver() {
            const sections = document.querySelectorAll('.doc-section');
            const observerOptions = {
                rootMargin: '-120px 0px -66% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.getAttribute('id');
                        document.querySelectorAll('.nav-link').forEach(link => {
                            link.classList.remove('bg-brand-50', 'text-brand-700', 'font-medium');
                            link.classList.add('text-neutral-600');
                        });
                        const activeLink = document.querySelector(`a[href="#${id}"]`);
                        if (activeLink) {
                            activeLink.classList.add('bg-brand-50', 'text-brand-700', 'font-medium');
                            activeLink.classList.remove('text-neutral-600');
                        }
                    }
                });
            }, observerOptions);

            sections.forEach(section => observer.observe(section));
        }

        document.addEventListener('DOMContentLoaded', () => {
            initChart();
            const dividendsCheckbox = document.getElementById('hasExcessDividends');
            if (dividendsCheckbox) {
                toggleDividendDetails(dividendsCheckbox);
                dividendsCheckbox.addEventListener('change', () => toggleDividendDetails(dividendsCheckbox));
            }
            setupRealtimeCalculation();
            if (hasSubmission) {
                showFinalResult();
            }
            setupScrollObserver();
        });
    </script>
</body>
</html>
