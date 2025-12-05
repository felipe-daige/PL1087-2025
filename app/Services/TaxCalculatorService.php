<?php

namespace App\Services;

use App\DTOs\TaxInputData;
use Illuminate\Http\Request;

/**
 * Serviço Orquestrador do Sistema de Inteligência Tributária
 * 
 * Coordena os 3 produtos de análise tributária baseados na Lei 15.270/2025:
 * - Produto 1: Comparativo Simplificada vs. Completa
 * - Produto 2: Simulação IRPFM (Imposto Mínimo)
 * - Produto 3: Comparativo Aluguéis PF vs. PJ
 * 
 * @see Lei 15.270/2025 (antiga PL 1.087/2025)
 */
class TaxCalculatorService
{
    private const CURRENT_YEAR = 2026;

    private TaxRegimeComparator $regimeComparator;
    private MinimumTaxCalculator $minimumTaxCalculator;
    private RentalTaxComparator $rentalTaxComparator;

    public function __construct(
        ?TaxRegimeComparator $regimeComparator = null,
        ?MinimumTaxCalculator $minimumTaxCalculator = null,
        ?RentalTaxComparator $rentalTaxComparator = null
    ) {
        $this->regimeComparator = $regimeComparator ?? new TaxRegimeComparator();
        $this->minimumTaxCalculator = $minimumTaxCalculator ?? new MinimumTaxCalculator();
        $this->rentalTaxComparator = $rentalTaxComparator ?? new RentalTaxComparator();
    }

    /**
     * Processa o Request e retorna todos os dados calculados
     * Ponto de entrada principal do sistema
     */
    public function calculate(Request $request): array
    {
        $submitted = $request->isMethod('post');
        
        // Criar DTO a partir do request
        $input = TaxInputData::fromRequest($request);

        // ========================================
        // PRODUTO 1: Comparativo de Regimes
        // ========================================
        $produto1 = $this->regimeComparator->compare($input);

        // ========================================
        // PRODUTO 2: IRPFM (Imposto Mínimo)
        // Usa o imposto tradicional calculado no Produto 1
        // ========================================
        $produto2 = $this->minimumTaxCalculator->calculate($input, $produto1['totalTax']);

        // ========================================
        // PRODUTO 3: Comparativo Aluguéis PF vs PJ
        // Usa a alíquota marginal do contribuinte
        // ========================================
        $marginalRate = $produto1['bestOption'] === 'simplified'
            ? $produto1['simplified']['effectiveRate'] / 100
            : $produto1['legal']['effectiveRate'] / 100;
        $produto3 = $this->rentalTaxComparator->generateProjection($input, $marginalRate);

        // ========================================
        // CONSOLIDAR RESULTADOS
        // ========================================
        $consolidatedResult = $this->consolidateResults($input, $produto1, $produto2, $produto3);

        // ========================================
        // MANTER COMPATIBILIDADE COM VIEW ATUAL
        // ========================================
        $legacyData = $this->buildLegacyData($request, $input, $produto1, $produto2, $submitted);

        return array_merge($legacyData, [
            'produtos' => [
                'regimeComparison' => $produto1,
                'minimumTax' => $produto2,
                'rentalComparison' => $produto3,
            ],
            'consolidated' => $consolidatedResult,
            'inputData' => $input,
        ]);
    }

    /**
     * Consolida os resultados dos 3 produtos em um resumo executivo
     */
    private function consolidateResults(
        TaxInputData $input,
        array $produto1,
        array $produto2,
        array $produto3
    ): array {
        // Imposto total considerando IRPFM
        $totalTraditionalTax = $produto1['totalTax'];
        $irpfmAdditional = $produto2['additionalTaxDue'] ?? 0;
        $totalTaxWithIRPFM = $totalTraditionalTax + $irpfmAdditional;

        // ========================================
        // LÓGICA CRÍTICA: Determinar qual regime vence
        // ========================================
        // IRPFM líquido (após crédito PJ)
        $irpfmTax = $produto2['minimumTaxNet'] ?? 0;
        // Imposto do regime geral (Simplificado ou Completo)
        $generalTax = $totalTraditionalTax;
        
        // Determinar qual regime vence
        $irpfmWins = $irpfmTax > $generalTax;
        
        // Calcular saldo final conforme regime vencedor
        if ($irpfmWins) {
            // CENÁRIO A: IRPFM vence - abate TODOS os impostos pagos (dedutíveis + exclusivos)
            $taxPaidDeductible = $input->getTotalTaxPaidDeductibleInGeneral();
            $taxPaidExclusive = $input->getTotalTaxPaidExclusive();
            $balance = $irpfmTax - ($taxPaidDeductible + $taxPaidExclusive);
        } else {
            // CENÁRIO B: Regime Geral vence - abate APENAS impostos dedutíveis
            // CRÍTICO: NÃO subtrai impostos de tributação exclusiva (JCP, Renda Fixa, Dividendos)
            $taxPaidDeductible = $input->getTotalTaxPaidDeductibleInGeneral();
            $balance = $generalTax - $taxPaidDeductible;
        }
        
        $isRefund = $balance < 0;
        
        // Manter compatibilidade: totalTaxWithIRPFM para exibição
        // Mas o saldo já foi calculado corretamente acima
        // Para exibição, manter o total de impostos pagos (para referência do usuário)
        $taxPaid = $input->getTotalTaxPaid();

        // Renda total
        $totalIncome = $input->getTotalIncomeForIRPFM();

        // Alíquota efetiva final
        $effectiveRate = $totalIncome > 0 ? ($totalTaxWithIRPFM / $totalIncome) * 100 : 0;

        // Economia potencial total
        $potentialSavings = $produto1['savings'];
        if (isset($produto3['comparison']['annualDifference']) && $produto3['comparison']['isPJBetter']) {
            $potentialSavings += $produto3['comparison']['annualDifference'];
        }

        return [
            // Resultado Principal
            'balance' => abs($balance),
            'isRefund' => $isRefund,
            'status' => $this->getResultStatus($balance),

            // Métricas Gerais
            'totalIncome' => $totalIncome,
            'totalTax' => $totalTaxWithIRPFM,
            'taxPaid' => $taxPaid,
            'effectiveRate' => $effectiveRate,

            // Breakdown
            'breakdown' => [
                'traditionalTax' => $totalTraditionalTax,
                'irpfmAdditional' => $irpfmAdditional,
                'dividendTax' => $input->exemptIncome->getDividendTax(),
                'jcpTax' => $input->exemptIncome->getJcpTax(),
            ],

            // Dados de Rendimentos Isentos/Exclusivos (para exibição no relatório)
            'exemptIncome' => [
                'dividendsTotal' => $input->exemptIncome->dividendsTotal,
                'dividendsExcess' => $input->exemptIncome->dividendsExcess,
                'dividendTax' => $input->exemptIncome->getDividendTax(),
                'jcpTotal' => $input->exemptIncome->jcpTotal,
                'jcpTax' => $input->exemptIncome->getJcpTax(),
                'irrfJcpWithheld' => $input->exemptIncome->irrfJcpWithheld,
                'irrfExclusiveOther' => $input->exemptIncome->irrfExclusiveOther,
                'financialInvestments' => $input->exemptIncome->financialInvestments,
            ],

            // Indicadores
            'indicators' => [
                'regimeBest' => $produto1['bestOption'],
                'regimeSavings' => $produto1['savings'],
                'irpfmTriggered' => $produto2['triggered'] ?? false,
                'irpfmAdditional' => $irpfmAdditional,
                'holdingRecommended' => ($produto3['comparison']['isPJBetter'] ?? false) 
                    && ($produto3['feasibility']['isViable'] ?? false),
                'holdingSavings' => $produto3['comparison']['annualDifference'] ?? 0,
            ],

            // Economia Potencial Total
            'potentialSavings' => $potentialSavings,

            // Alertas Consolidados
            'alerts' => $this->consolidateAlerts($produto1, $produto2, $produto3),
        ];
    }

    /**
     * Consolida alertas de todos os produtos
     */
    private function consolidateAlerts(array $produto1, array $produto2, array $produto3): array
    {
        $alerts = [];

        // Alertas do Produto 1
        if (!empty($produto1['alerts'])) {
            $alerts = array_merge($alerts, $produto1['alerts']);
        }

        // Alertas do Produto 2 (IRPFM)
        if ($produto2['triggered'] ?? false) {
            $rate = number_format($produto2['minimumRate'], 1, ',', '.');
            $alerts[] = "IRPFM ativado: Alíquota mínima de {$rate}% aplicável.";
            
            if (($produto2['pjTaxCredit'] ?? 0) > 0) {
                $credit = $this->formatCurrency($produto2['pjTaxCredit']);
                $alerts[] = "Crédito de IRPJ/CSLL aplicado: {$credit}.";
            }
        }

        // Alertas do Produto 3 (Holding)
        if (isset($produto3['comparison']['isPJBetter']) && $produto3['comparison']['isPJBetter']) {
            if ($produto3['feasibility']['isViable'] ?? false) {
                $savings = $this->formatCurrency($produto3['feasibility']['netMonthlySavings'] * 12);
                $alerts[] = "Oportunidade: Holding Patrimonial pode economizar {$savings}/ano.";
            }
        }

        return $alerts;
    }

    /**
     * Constrói dados no formato legado para compatibilidade com a view atual
     */
    private function buildLegacyData(
        Request $request,
        TaxInputData $input,
        array $produto1,
        array $produto2,
        bool $submitted
    ): array {
        // Estado legado para o formulário
        $state = [
            'birthDate' => $input->birthDate,
            // Retrocompatibilidade: manter birthYear para dados antigos
            'birthYear' => $input->birthDate ? (int) substr($input->birthDate, 0, 4) : null,
            'seriousIllness' => $input->hasSeriousIllness,
            'incomeSources' => array_map(fn($s) => $s->toArray(), $input->incomeSources),
            'rentalProperties' => array_map(fn($p) => $p->toArray(), $input->rentalProperties),
            'incomeMonthly' => $input->getTotalGrossMonthly(),
            'income13' => $input->income13,
            'dividendsTotal' => $input->exemptIncome->dividendsTotal,
            'hasExcessDividends' => $input->exemptIncome->dividendsExcess > 0,
            'dividendsExcess' => $input->exemptIncome->dividendsExcess,
            'jcpTotal' => $input->exemptIncome->jcpTotal,
            'financialInvestments' => $input->exemptIncome->financialInvestments,
            'taxExemptInvestments' => $input->exemptIncome->taxExemptInvestments,
            'fiiDividends' => $input->exemptIncome->fiiDividends,
            'incomeOther' => $input->getTotalRentalNetMonthly(),
            'taxPaid' => $input->taxPaidOther + $input->getTotalIrrfWithheld(), // Já retorna anual
            'totalInssRetido' => $input->getTotalInssWithheld(), // Já retorna anual
            'dependents' => $input->dependents,
            'deductionHealth' => $input->deductionHealth,
            'deductionEducation' => $input->deductionEducation,
            'deductionPGBL' => $input->deductionPGBL,
            'bookExpenses' => $input->bookExpenses,
            'alimonyPaid' => $input->alimonyPaid,
            'highIncomeTrigger' => $input->triggersIRPFM(),
            // Dados corporativos
            'accountingProfit' => $input->corporateData?->accountingProfit ?? 0,
            'distributedProfit' => $input->corporateData?->distributedProfit ?? 0,
            'irpjPaid' => $input->corporateData?->irpjPaid ?? 0,
            'csllPaid' => $input->corporateData?->csllPaid ?? 0,
            'ownershipPercentage' => $input->corporateData?->ownershipPercentage ?? 100,
        ];

        // ========================================
        // LÓGICA CRÍTICA: Calcular resultado final conforme regime vencedor
        // ========================================
        // IRPFM líquido (após crédito PJ)
        $irpfmTax = $produto2['minimumTaxNet'] ?? 0;
        // Imposto do regime geral (Simplificado ou Completo)
        $generalTax = $produto1['totalTax'];
        
        // Determinar qual regime vence
        $irpfmWins = $irpfmTax > $generalTax;
        
        // Calcular saldo final conforme regime vencedor
        if ($irpfmWins) {
            // CENÁRIO A: IRPFM vence - abate TODOS os impostos pagos (dedutíveis + exclusivos)
            $taxPaidDeductible = $input->getTotalTaxPaidDeductibleInGeneral();
            $taxPaidExclusive = $input->getTotalTaxPaidExclusive();
            $finalResult = $irpfmTax - ($taxPaidDeductible + $taxPaidExclusive);
        } else {
            // CENÁRIO B: Regime Geral vence - abate APENAS impostos dedutíveis
            // CRÍTICO: NÃO subtrai impostos de tributação exclusiva (JCP, Renda Fixa, Dividendos)
            $taxPaidDeductible = $input->getTotalTaxPaidDeductibleInGeneral();
            $finalResult = $generalTax - $taxPaidDeductible;
        }
        
        $isNegativeFinalResult = $finalResult < 0;
        
        // Para compatibilidade: manter totalTax como soma tradicional + adicional IRPFM
        $totalTax = $produto1['totalTax'] + ($produto2['additionalTaxDue'] ?? 0);
        // Mas o finalResult já foi calculado corretamente acima conforme regime vencedor

        // Determinar estilo do resultado
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

        // Recomendação do regime
        $recommendationText = $produto1['recommendation'];
        if ($produto1['bestOption'] === 'simplified') {
            $recommendationText = '<span class="text-brand-700 font-bold">Recomendação:</span> ' . $recommendationText;
        } else {
            $recommendationText = '<span class="text-green-700 font-bold">Recomendação:</span> ' . $recommendationText;
        }

        // Alíquota efetiva
        $totalIncome = $input->getTotalIncomeForIRPFM();
        $effectiveRate = $totalIncome > 0 ? ($totalTax / $totalIncome) * 100 : 0;

        return [
            'request' => $request,
            'submitted' => $submitted,
            'currentYear' => self::CURRENT_YEAR,
            'constants' => $produto1['constants'],
            'state' => $state,
            
            // Dados para o resultado
            'grossTaxable' => $produto1['grossTaxable'],
            'simplifiedDiscount' => $produto1['simplified']['discount'],
            'taxSimplified' => $produto1['simplified']['tax'],
            'educationWarning' => $produto1['legal']['deductions']['education']['warning'] ?? false,
            'educationDeduction' => $produto1['legal']['deductions']['education']['value'] ?? 0,
            'pgblCap' => $produto1['legal']['deductions']['pgbl']['cap'] ?? 0,
            'pgblDeduction' => $produto1['legal']['deductions']['pgbl']['value'] ?? 0,
            'totalDeductions' => $produto1['legal']['deductions']['total'] ?? 0,
            'baseLegal' => $produto1['legal']['base'],
            'taxLegal' => $produto1['legal']['tax'],
            'dividendTax' => $input->exemptIncome->getDividendTax(),
            'tax13' => $produto1['tax13'],
            'bestTaxOption' => $produto1['bestTax'],
            'isSimplifiedBetter' => $produto1['bestOption'] === 'simplified',
            'totalTaxLiability' => $totalTax,
            'finalResult' => $finalResult,
            'isNegativeFinalResult' => $isNegativeFinalResult,
            'displayFinalResult' => abs($finalResult),
            'totalIncome' => $totalIncome,
            'effectiveRate' => $effectiveRate,
            'resultLabel' => $resultLabel,
            'resultClass' => $resultClass,
            'cardBorder' => $cardBorder,
            'recommendationText' => $recommendationText,
            
            // Dados para o gráfico
            'chartData' => [
                'simplified' => round($produto1['simplified']['tax'] + $input->exemptIncome->getDividendTax(), 2),
                'legal' => round($produto1['legal']['tax'] + $input->exemptIncome->getDividendTax(), 2),
            ],
            
            // Alertas consolidados
            'alerts' => $this->consolidateAlerts($produto1, $produto2, []),
        ];
    }

    /**
     * Retorna o status do resultado
     */
    private function getResultStatus(float $balance): string
    {
        if ($balance > 0) {
            return 'pay';
        } elseif ($balance < 0) {
            return 'refund';
        }
        return 'zero';
    }

    // ========================================
    // MÉTODOS UTILITÁRIOS (mantidos para compatibilidade)
    // ========================================

    /**
     * Converte valores para float, tratando formatos brasileiros
     */
    public function irpfToFloat($value): float
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

    /**
     * Formata valores como moeda brasileira
     */
    public function irpfCurrency(float $value): string
    {
        return $this->formatCurrency($value);
    }

    /**
     * Formata valor como moeda brasileira
     */
    public function formatCurrency(float $value): string
    {
        $prefix = $value < 0 ? '- ' : '';
        return $prefix . 'R$ ' . number_format(abs($value), 2, ',', '.');
    }

    /**
     * Obtém valores dos campos do formulário
     */
    public function irpfFieldValue(Request $request, string $key, $stateValue, bool $submitted)
    {
        $rawValue = $request->input($key);
        if ($rawValue !== null && $rawValue !== '') {
            return $rawValue;
        }

        return $submitted ? $stateValue : '';
    }

    /**
     * Calcula imposto progressivo IRPF anual (método legado)
     * @deprecated Use TaxRegimeComparator::compare() para cálculos completos
     */
    public function calculateProgressiveTaxIRPF(float $annualBase): float
    {
        if ($annualBase <= 0) {
            return 0.0;
        }

        $monthlyBase = $annualBase / 12;
        $monthlyTax = $this->calculateMonthlyTaxIRPF($monthlyBase);

        return max(0, $monthlyTax * 12);
    }

    /**
     * Calcula imposto progressivo IRPF mensal (método legado)
     * @deprecated Use TaxRegimeComparator para cálculos com a nova tabela
     */
    public function calculateMonthlyTaxIRPF(float $monthlyBase): float
    {
        if ($monthlyBase <= 0) {
            return 0.0;
        }

        // Tabela Lei 15.270/2025
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
