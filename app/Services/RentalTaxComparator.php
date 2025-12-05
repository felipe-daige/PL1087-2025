<?php

namespace App\Services;

use App\DTOs\TaxInputData;
use App\DTOs\RentalProperty;

/**
 * PRODUTO 3: Comparativo Aluguéis PF vs PJ (Planejamento Tributário)
 * 
 * Compara a carga tributária de rendimentos de aluguéis
 * quando recebidos como Pessoa Física vs. através de uma
 * Holding Patrimonial (Pessoa Jurídica - Lucro Presumido).
 * 
 * @see Lei 15.270/2025 - Planejamento Tributário
 */
class RentalTaxComparator
{
    // ========================================
    // CONSTANTES - TRIBUTAÇÃO PF
    // ========================================

    /**
     * Alíquota máxima do IRPF (27,5%)
     */
    private const IRPF_MAX_RATE = 0.275;

    /**
     * Tabela progressiva mensal do IRPF
     * @see Lei 15.270/2025 - Art. 3º-A
     */
    private const TAX_BRACKETS_MONTHLY = [
        ['limit' => 5000.00,  'rate' => 0.000, 'deduction' => 0.00],
        ['limit' => 7350.00,  'rate' => 0.075, 'deduction' => 375.00],
        ['limit' => 9250.00,  'rate' => 0.150, 'deduction' => 926.25],
        ['limit' => 12000.00, 'rate' => 0.225, 'deduction' => 1620.00],
        ['limit' => PHP_FLOAT_MAX, 'rate' => 0.275, 'deduction' => 2220.00],
    ];

    // ========================================
    // CONSTANTES - TRIBUTAÇÃO PJ (LUCRO PRESUMIDO)
    // ========================================

    /**
     * Base de presunção para atividade de aluguéis
     * 32% da receita bruta
     */
    private const PRESUMED_PROFIT_RATE = 0.32;

    /**
     * Alíquota do IRPJ
     */
    private const IRPJ_RATE = 0.15;

    /**
     * Limite mensal para adicional de IRPJ
     */
    private const IRPJ_ADDITIONAL_THRESHOLD = 20000.00;

    /**
     * Alíquota do adicional de IRPJ (lucro > R$ 20k/mês)
     */
    private const IRPJ_ADDITIONAL_RATE = 0.10;

    /**
     * Alíquota da CSLL
     */
    private const CSLL_RATE = 0.09;

    /**
     * Alíquota de PIS/COFINS (regime cumulativo)
     */
    private const PIS_COFINS_RATE = 0.0365;

    /**
     * Alíquota de ISS (se aplicável - varia por município)
     * Alguns municípios isentam locação de imóveis próprios
     */
    private const ISS_RATE = 0.00;

    // ========================================
    // CONSTANTES - TRIBUTAÇÃO DE DIVIDENDOS
    // ========================================

    /**
     * Isenção mensal de dividendos por fonte
     * @see Lei 15.270/2025 - Art. 5º
     */
    private const DIVIDEND_EXEMPTION_MONTHLY = 50000.00;

    /**
     * Alíquota sobre dividendos excedentes
     * @see Lei 15.270/2025 - Art. 5º
     */
    private const DIVIDEND_TAX_RATE = 0.10;

    /**
     * Executa o comparativo PF vs PJ
     */
    public function compare(TaxInputData $input, float $currentMarginalRate = 0): array
    {
        // Se não há imóveis, retornar resultado vazio
        if (empty($input->rentalProperties)) {
            return [
                'hasProperties' => false,
                'hasIncome' => false,
                'message' => 'Nenhum imóvel de aluguel informado.',
            ];
        }

        // Calcular totais
        $totalGrossMonthly = $input->getTotalRentalGrossMonthly();
        $totalGrossAnnual = $totalGrossMonthly * 12;

        // Se não há renda de aluguel, retornar resultado vazio
        if ($totalGrossMonthly <= 0) {
            return [
                'hasProperties' => true,
                'hasIncome' => false,
                'message' => 'Nenhuma receita de aluguel informada.',
            ];
        }

        // Calcular cenário PF
        $pfScenario = $this->calculatePFScenario($input, $currentMarginalRate);

        // Calcular cenário PJ
        $pjScenario = $this->calculatePJScenario($input);

        // Comparativo
        $monthlyDifference = $pfScenario['monthlyTax'] - $pjScenario['totalMonthlyTax'];
        $annualDifference = $monthlyDifference * 12;
        $isPJBetter = $pjScenario['totalMonthlyTax'] < $pfScenario['monthlyTax'];

        // Calcular payback da constituição da PJ (custo estimado)
        $pjSetupCost = 5000.00; // Custo estimado de abertura
        $pjMonthlyCost = 500.00; // Contador + taxas mensais
        $paybackMonths = $monthlyDifference > $pjMonthlyCost 
            ? ceil($pjSetupCost / ($monthlyDifference - $pjMonthlyCost))
            : null;

        return [
            'hasProperties' => true,
            'hasIncome' => true,

            // Resumo das propriedades
            'properties' => [
                'count' => count($input->rentalProperties),
                'totalGrossMonthly' => $totalGrossMonthly,
                'totalGrossAnnual' => $totalGrossAnnual,
                'details' => array_map(fn($p) => $p->toArray(), $input->rentalProperties),
            ],

            // Cenário PF
            'pfScenario' => $pfScenario,

            // Cenário PJ
            'pjScenario' => $pjScenario,

            // Comparativo
            'comparison' => [
                'monthlyDifference' => abs($monthlyDifference),
                'annualDifference' => abs($annualDifference),
                'isPJBetter' => $isPJBetter,
                'savingsPercentage' => $pfScenario['monthlyTax'] > 0 
                    ? abs($monthlyDifference / $pfScenario['monthlyTax']) * 100 
                    : 0,
            ],

            // Análise de viabilidade
            'feasibility' => [
                'estimatedSetupCost' => $pjSetupCost,
                'estimatedMonthlyCost' => $pjMonthlyCost,
                'netMonthlySavings' => max(0, $monthlyDifference - $pjMonthlyCost),
                'paybackMonths' => $paybackMonths,
                'isViable' => $isPJBetter && $monthlyDifference > $pjMonthlyCost,
            ],

            // Recomendação
            'recommendation' => $this->generateRecommendation(
                $isPJBetter,
                $monthlyDifference,
                $pjMonthlyCost,
                $totalGrossMonthly
            ),

            // Constantes para referência
            'constants' => [
                'presumedProfitRate' => self::PRESUMED_PROFIT_RATE * 100,
                'irpjRate' => self::IRPJ_RATE * 100,
                'csllRate' => self::CSLL_RATE * 100,
                'pisCofinsRate' => self::PIS_COFINS_RATE * 100,
                'dividendExemption' => self::DIVIDEND_EXEMPTION_MONTHLY,
                'dividendTaxRate' => self::DIVIDEND_TAX_RATE * 100,
            ],
        ];
    }

    /**
     * Calcula o cenário de tributação como Pessoa Física
     * 
     * PF: Aluguel líquido entra na base progressiva do IRPF
     * Dedutíveis: Taxa de administração, IPTU, Condomínio (se pagos pelo proprietário)
     */
    private function calculatePFScenario(TaxInputData $input, float $currentMarginalRate): array
    {
        $grossMonthly = $input->getTotalRentalGrossMonthly();
        $netMonthly = $input->getTotalRentalNetMonthly(); // Após deduções
        $deductionsMonthly = $grossMonthly - $netMonthly;

        // Calcular imposto considerando a alíquota marginal do contribuinte
        // Se já tem outras rendas, o aluguel entra na faixa mais alta
        $effectiveRate = $currentMarginalRate > 0 
            ? $currentMarginalRate 
            : $this->estimateMarginalRate($netMonthly);

        $monthlyTax = $netMonthly * $effectiveRate;
        $annualTax = $monthlyTax * 12;

        // Líquido disponível
        $netAfterTax = $netMonthly - $monthlyTax;

        return [
            'grossMonthly' => $grossMonthly,
            'deductionsMonthly' => $deductionsMonthly,
            'netMonthly' => $netMonthly,
            'marginalRate' => $effectiveRate * 100,
            'monthlyTax' => $monthlyTax,
            'annualTax' => $annualTax,
            'netAfterTax' => $netAfterTax,
            'effectiveRate' => $grossMonthly > 0 ? ($monthlyTax / $grossMonthly) * 100 : 0,
        ];
    }

    /**
     * Calcula o cenário de tributação como Pessoa Jurídica (Lucro Presumido)
     * 
     * PJ Lucro Presumido para locação de imóveis:
     * - Base de presunção: 32%
     * - IRPJ: 15% (+ 10% adicional se lucro > R$ 20k/mês)
     * - CSLL: 9%
     * - PIS/COFINS: 3,65% (cumulativo)
     * 
     * Após impostos PJ, a distribuição de lucros é tributada
     * conforme Lei 15.270/2025 (10% sobre excedente acima de R$ 600k/ano - limite anualizado)
     */
    private function calculatePJScenario(TaxInputData $input): array
    {
        $grossMonthly = $input->getTotalRentalGrossMonthly();
        $grossAnnual = $grossMonthly * 12;

        // ========================================
        // IMPOSTOS NA PJ
        // ========================================

        // Base presumida mensal (32% da receita bruta)
        $presumedProfitMonthly = $grossMonthly * self::PRESUMED_PROFIT_RATE;
        $presumedProfitAnnual = $grossAnnual * self::PRESUMED_PROFIT_RATE;

        // IRPJ (15% sobre base presumida)
        $irpjMonthly = $presumedProfitMonthly * self::IRPJ_RATE;

        // Adicional de IRPJ (10% sobre excedente de R$ 20k/mês)
        $irpjAdditionalMonthly = $presumedProfitMonthly > self::IRPJ_ADDITIONAL_THRESHOLD
            ? ($presumedProfitMonthly - self::IRPJ_ADDITIONAL_THRESHOLD) * self::IRPJ_ADDITIONAL_RATE
            : 0;

        // CSLL (9% sobre base presumida)
        $csllMonthly = $presumedProfitMonthly * self::CSLL_RATE;

        // PIS/COFINS (3,65% sobre receita bruta - regime cumulativo)
        $pisCofinsMonthly = $grossMonthly * self::PIS_COFINS_RATE;

        // Total de impostos na PJ
        $totalPJTaxMonthly = $irpjMonthly + $irpjAdditionalMonthly + $csllMonthly + $pisCofinsMonthly;

        // ========================================
        // LUCRO DISTRIBUÍVEL
        // ========================================

        // Lucro após impostos (simplificado - sem considerar despesas operacionais)
        $distributableProfit = $grossMonthly - $totalPJTaxMonthly;

        // ========================================
        // TRIBUTAÇÃO DE DIVIDENDOS (Lei 15.270/2025)
        // Art. 5º e Art. 6º-A - 10% sobre excedente acima de R$ 600k/ano (limite anualizado)
        // ========================================
        $dividendTax = 0;
        if ($distributableProfit > self::DIVIDEND_EXEMPTION_MONTHLY) {
            $excessDividend = $distributableProfit - self::DIVIDEND_EXEMPTION_MONTHLY;
            $dividendTax = $excessDividend * self::DIVIDEND_TAX_RATE;
        }

        // Total de impostos (PJ + Dividendos)
        $totalTaxMonthly = $totalPJTaxMonthly + $dividendTax;
        $totalTaxAnnual = $totalTaxMonthly * 12;

        // Líquido final para o sócio
        $netAfterAllTaxes = $distributableProfit - $dividendTax;

        // Alíquota efetiva total
        $effectiveRate = $grossMonthly > 0 ? ($totalTaxMonthly / $grossMonthly) * 100 : 0;

        return [
            'grossMonthly' => $grossMonthly,
            
            // Impostos na PJ
            'pjTaxes' => [
                'presumedProfit' => $presumedProfitMonthly,
                'irpj' => $irpjMonthly,
                'irpjAdditional' => $irpjAdditionalMonthly,
                'csll' => $csllMonthly,
                'pisCofins' => $pisCofinsMonthly,
                'total' => $totalPJTaxMonthly,
                'effectiveRate' => $grossMonthly > 0 ? ($totalPJTaxMonthly / $grossMonthly) * 100 : 0,
            ],
            
            // Distribuição de lucros
            'distribution' => [
                'distributableProfit' => $distributableProfit,
                'exemptAmount' => min($distributableProfit, self::DIVIDEND_EXEMPTION_MONTHLY),
                'taxableExcess' => max(0, $distributableProfit - self::DIVIDEND_EXEMPTION_MONTHLY),
                'dividendTax' => $dividendTax,
            ],
            
            // Totais
            'totalMonthlyTax' => $totalTaxMonthly,
            'totalAnnualTax' => $totalTaxAnnual,
            'netAfterAllTaxes' => $netAfterAllTaxes,
            'effectiveRate' => $effectiveRate,
        ];
    }

    /**
     * Estima a alíquota marginal do contribuinte baseado na renda
     */
    private function estimateMarginalRate(float $monthlyIncome): float
    {
        foreach (self::TAX_BRACKETS_MONTHLY as $bracket) {
            if ($monthlyIncome <= $bracket['limit']) {
                return $bracket['rate'];
            }
        }
        return self::IRPF_MAX_RATE;
    }

    /**
     * Gera recomendação baseada na análise
     */
    private function generateRecommendation(
        bool $isPJBetter,
        float $monthlyDifference,
        float $pjMonthlyCost,
        float $grossMonthly
    ): string {
        // Diferença muito pequena
        if (abs($monthlyDifference) < 500) {
            return 'Diferença tributária pouco significativa. Avalie outros fatores como proteção patrimonial e sucessão.';
        }

        if (!$isPJBetter) {
            return 'Manter os aluguéis na Pessoa Física é mais vantajoso tributariamente.';
        }

        $netSavings = $monthlyDifference - $pjMonthlyCost;

        if ($netSavings <= 0) {
            return 'A economia tributária não compensa os custos operacionais da PJ. Considere apenas se houver interesse em proteção patrimonial.';
        }

        $savingsFormatted = 'R$ ' . number_format($netSavings, 2, ',', '.');
        $annualSavings = 'R$ ' . number_format($netSavings * 12, 2, ',', '.');

        if ($grossMonthly >= 30000) {
            return "Holding Patrimonial altamente recomendada. Economia líquida de {$savingsFormatted}/mês ({$annualSavings}/ano), além de benefícios de proteção patrimonial e planejamento sucessório.";
        }

        return "Holding Patrimonial pode ser vantajosa. Economia líquida estimada de {$savingsFormatted}/mês. Consulte um contador para análise detalhada.";
    }

    /**
     * Gera projeção de 12 meses comparando os cenários
     */
    public function generateProjection(TaxInputData $input, float $currentMarginalRate = 0): array
    {
        $comparison = $this->compare($input, $currentMarginalRate);
        
        // Retornar se não há imóveis ou renda
        if (!($comparison['hasIncome'] ?? false)) {
            return $comparison;
        }

        $months = [];
        $cumulativePF = 0;
        $cumulativePJ = 0;

        for ($i = 1; $i <= 12; $i++) {
            $cumulativePF += $comparison['pfScenario']['monthlyTax'];
            $cumulativePJ += $comparison['pjScenario']['totalMonthlyTax'];

            $months[] = [
                'month' => $i,
                'pfTax' => $comparison['pfScenario']['monthlyTax'],
                'pjTax' => $comparison['pjScenario']['totalMonthlyTax'],
                'cumulativePF' => $cumulativePF,
                'cumulativePJ' => $cumulativePJ,
                'cumulativeSavings' => $cumulativePF - $cumulativePJ,
            ];
        }

        $comparison['projection'] = $months;
        
        return $comparison;
    }
}

