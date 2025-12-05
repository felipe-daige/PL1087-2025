<?php

namespace App\Services;

use App\DTOs\TaxInputData;
use Carbon\Carbon;

/**
 * PRODUTO 1: Comparativo Simplificada vs. Completa
 * 
 * Compara os dois regimes de tributação do IRPF e identifica
 * qual oferece menor carga tributária para o contribuinte.
 * 
 * @see Lei 15.270/2025 - Art. 3º-A (Nova tabela progressiva)
 */
class TaxRegimeComparator
{
    // ========================================
    // CONSTANTES DA LEI 15.270/2025
    // ========================================

    /**
     * Art. 3º-A - Nova faixa de isenção até R$ 5.000,00
     * Contribuintes com rendimentos até este valor têm imposto zero
     */
    private const TAX_REDUCTION_FULL_LIMIT = 5000.00;

    /**
     * Art. 3º-A - Faixa de transição até R$ 7.350,00
     * A redução decresce linearmente até zerar
     */
    private const TAX_REDUCTION_PHASEOUT_LIMIT = 7350.00;

    /**
     * Art. 3º-A - Redução máxima de imposto
     */
    private const TAX_REDUCTION_MAX_VALUE = 312.89;

    /**
     * Art. 3º-A - Constante da fórmula de redução
     */
    private const TAX_REDUCTION_FORMULA_CONSTANT = 978.62;

    /**
     * Art. 3º-A - Fator de decréscimo linear
     */
    private const TAX_REDUCTION_FACTOR = 0.133145;

    /**
     * Desconto Simplificado - 20% limitado ao teto
     */
    private const SIMPLIFIED_RATE = 0.20;
    private const SIMPLIFIED_CAP = 16754.34;

    /**
     * Dedução por dependente (valor anual)
     */
    private const DEDUCTION_PER_DEPENDENT = 2275.08;

    /**
     * Teto de dedução com educação (por pessoa)
     */
    private const EDUCATION_CAP = 3561.50;

    /**
     * Isenção adicional para maiores de 65 anos (anual)
     */
    private const SENIOR_EXEMPTION = 24000.00;

    /**
     * Teto anual do INSS
     */
    private const INSS_CEILING_ANNUAL = 10874.30;

    // ========================================
    // TABELA PROGRESSIVA - Lei 15.270/2025, Art. 3º-A
    // ========================================

    /**
     * Nova tabela progressiva mensal (vigência: janeiro/2026)
     * 
     * @see Lei 15.270/2025 - Art. 3º-A
     */
    private const TAX_BRACKETS_MONTHLY = [
        ['limit' => 5000.00,  'rate' => 0.000, 'deduction' => 0.00],
        ['limit' => 7350.00,  'rate' => 0.075, 'deduction' => 375.00],
        ['limit' => 9250.00,  'rate' => 0.150, 'deduction' => 926.25],
        ['limit' => 12000.00, 'rate' => 0.225, 'deduction' => 1620.00],
        ['limit' => PHP_FLOAT_MAX, 'rate' => 0.275, 'deduction' => 2220.00],
    ];

    /**
     * Executa o comparativo entre os regimes
     */
    public function compare(TaxInputData $input): array
    {
        $currentYear = 2026;
        
        // Calcular base de cálculo bruta tributável
        $grossTaxable = $this->calculateGrossTaxable($input, $currentYear);
        
        // Calcular INSS dedutível (respeitando o teto global)
        $inssDeductible = $this->calculateInssDeductible($input);
        
        // Base após INSS
        $baseAfterInss = $grossTaxable - $inssDeductible;

        // Subtrair pensão alimentícia judicial (dedução legal integral que afeta ambos os regimes)
        // Deve ser deduzida antes de aplicar o desconto simplificado, conforme regra da Receita
        $baseAfterInss = max(0, $baseAfterInss - $input->alimonyPaid);

        // ========================================
        // REGIME 1: DESCONTO SIMPLIFICADO
        // ========================================
        $simplifiedDiscount = min(
            $baseAfterInss * self::SIMPLIFIED_RATE,
            self::SIMPLIFIED_CAP
        );
        $baseSimplified = max(0, $baseAfterInss - $simplifiedDiscount);
        $taxSimplified = $this->calculateProgressiveTax($baseSimplified);

        // ========================================
        // REGIME 2: DEDUÇÕES LEGAIS (COMPLETA)
        // ========================================
        $deductionsResult = $this->calculateLegalDeductions($input, $baseAfterInss);
        $baseLegal = max(0, $baseAfterInss - $deductionsResult['total']);
        $taxLegal = $this->calculateProgressiveTax($baseLegal);

        // ========================================
        // IMPOSTO SOBRE 13º (TRIBUTAÇÃO EXCLUSIVA)
        // ========================================
        $tax13 = $this->calculateMonthlyTax($input->income13);

        // ========================================
        // RESULTADO DO COMPARATIVO
        // ========================================
        $isSimplifiedBetter = $taxSimplified < $taxLegal;
        $bestTax = min($taxSimplified, $taxLegal);
        $savings = abs($taxSimplified - $taxLegal);

        return [
            // Inputs processados
            'grossTaxable' => $grossTaxable,
            'inssDeductible' => $inssDeductible,
            'baseAfterInss' => $baseAfterInss,

            // Regime Simplificado
            'simplified' => [
                'discount' => $simplifiedDiscount,
                'base' => $baseSimplified,
                'tax' => $taxSimplified,
                'effectiveRate' => $baseAfterInss > 0 ? ($taxSimplified / $baseAfterInss) * 100 : 0,
            ],

            // Regime Completo (Deduções Legais)
            'legal' => [
                'deductions' => $deductionsResult,
                'base' => $baseLegal,
                'tax' => $taxLegal,
                'effectiveRate' => $baseAfterInss > 0 ? ($taxLegal / $baseAfterInss) * 100 : 0,
            ],

            // 13º Salário
            'tax13' => $tax13,

            // Resultado
            'bestOption' => $isSimplifiedBetter ? 'simplified' : 'legal',
            'bestTax' => $bestTax,
            'totalTax' => $bestTax + $tax13,
            'savings' => $savings,
            'savingsPercentage' => $taxSimplified > 0 || $taxLegal > 0 
                ? ($savings / max($taxSimplified, $taxLegal)) * 100 
                : 0,

            // Recomendação
            'recommendation' => $this->generateRecommendation($isSimplifiedBetter, $savings),

            // Alertas
            'alerts' => $deductionsResult['alerts'],

            // Constantes utilizadas (para referência no frontend)
            'constants' => [
                'simplifiedRate' => self::SIMPLIFIED_RATE,
                'simplifiedCap' => self::SIMPLIFIED_CAP,
                'educationCap' => self::EDUCATION_CAP,
                'deductionPerDependent' => self::DEDUCTION_PER_DEPENDENT,
                'inssCeiling' => self::INSS_CEILING_ANNUAL,
            ],
        ];
    }

    /**
     * Calcula a base bruta tributável anual
     * Aplica isenções de 65+ e moléstia grave
     * 
     * @see Lei 15.270/2025 - Art. 3º-A, §2º (Isenções mantidas)
     */
    private function calculateGrossTaxable(TaxInputData $input, int $currentYear): float
    {
        // Renda tributável anual (salários + aluguéis líquidos)
        $grossTaxable = $input->getTotalGrossAnnual() + $input->getTotalRentalNetMonthly() * 12;

        // Subtrair despesas de livro caixa APENAS da renda autônoma
        // O Livro Caixa só pode abater receita de autônomo, não salário CLT
        // Exemplo: Se tiver R$ 100k de salário + R$ 5k de autônomo e R$ 20k de despesas,
        // a base deve ser: 100k (salário) + 0 (autônomo zerado), não 85k (100k+5k-20k)
        $autonomousIncome = $input->getTotalAutonomousAnnual();
        $bookExpensesDeductible = min($input->bookExpenses, $autonomousIncome);
        $grossTaxable = max(0, $grossTaxable - $bookExpensesDeductible);

        // Isenção para moléstia grave: zera rendimentos de aposentadoria
        // Art. 6º, XIV da Lei 7.713/88 (mantido pela Lei 15.270)
        if ($input->hasSeriousIllness) {
            // Remove rendimentos de aposentadoria da base
            $retirementIncome = array_reduce(
                $input->incomeSources,
                fn($total, $source) => $source->type === 'retirement' 
                    ? $total + $source->getGrossAnnual() 
                    : $total,
                0.0
            );
            $grossTaxable = max(0, $grossTaxable - $retirementIncome);
        }

        // Isenção adicional para maiores de 65 anos
        // Art. 6º, XV da Lei 7.713/88 (mantido pela Lei 15.270)
        // Converter ano para Carbon para compatibilidade com nova assinatura
        $currentDate = Carbon::create($currentYear, 1, 1);
        if ($input->isEligibleForSeniorExemption($currentDate)) {
            $grossTaxable = max(0, $grossTaxable - self::SENIOR_EXEMPTION);
        }

        return $grossTaxable;
    }

    /**
     * Calcula o INSS dedutível respeitando o teto global
     * Quando há múltiplas fontes, o teto é único
     */
    private function calculateInssDeductible(TaxInputData $input): float
    {
        $totalInssAnnual = $input->getTotalInssWithheld(); // Já retorna anual
        
        // Respeitar teto global do INSS
        return min($totalInssAnnual, self::INSS_CEILING_ANNUAL);
    }

    /**
     * Calcula todas as deduções legais
     */
    private function calculateLegalDeductions(TaxInputData $input, float $grossTaxable): array
    {
        $alerts = [];

        // Dedução por dependentes
        $dependentsDeduction = $input->dependents * self::DEDUCTION_PER_DEPENDENT;

        // Dedução com saúde (sem limite)
        $healthDeduction = $input->deductionHealth;

        // Dedução com educação (limitada ao teto por pessoa)
        // Considera dependentes + titular
        $educationMaxTotal = self::EDUCATION_CAP * ($input->dependents + 1);
        $educationDeduction = $input->deductionEducation;
        $educationWarning = false;
        
        if ($educationDeduction > $educationMaxTotal) {
            $educationWarning = true;
            $educationDeduction = $educationMaxTotal;
            $alerts[] = "Limite de educação aplicado: R$ " . number_format($educationMaxTotal, 2, ',', '.');
        }

        // Dedução PGBL (limitada a 12% da renda bruta tributável)
        $pgblCap = $grossTaxable * 0.12;
        $pgblDeduction = min($input->deductionPGBL, $pgblCap);
        $pgblWarning = false;
        
        if ($input->deductionPGBL > $pgblCap) {
            $pgblWarning = true;
            $alerts[] = "PGBL limitado a 12% da renda: R$ " . number_format($pgblCap, 2, ',', '.');
        }

        $total = $dependentsDeduction + $healthDeduction + $educationDeduction + $pgblDeduction;

        return [
            'dependents' => [
                'count' => $input->dependents,
                'value' => $dependentsDeduction,
            ],
            'health' => $healthDeduction,
            'education' => [
                'value' => $educationDeduction,
                'original' => $input->deductionEducation,
                'warning' => $educationWarning,
            ],
            'pgbl' => [
                'value' => $pgblDeduction,
                'original' => $input->deductionPGBL,
                'cap' => $pgblCap,
                'warning' => $pgblWarning,
            ],
            'total' => $total,
            'alerts' => $alerts,
        ];
    }

    /**
     * Calcula imposto progressivo anual
     * 
     * @see Lei 15.270/2025 - Art. 3º-A (Tabela progressiva)
     */
    private function calculateProgressiveTax(float $annualBase): float
    {
        if ($annualBase <= 0) {
            return 0.0;
        }

        $monthlyBase = $annualBase / 12;
        $monthlyTax = $this->calculateMonthlyTax($monthlyBase);

        return $monthlyTax * 12;
    }

    /**
     * Calcula imposto mensal aplicando a tabela progressiva e a redução do Art. 3º-A
     * 
     * A Lei 15.270/2025 institui uma redução de imposto para rendimentos até R$ 7.350:
     * - Até R$ 5.000: Redução total (imposto zero)
     * - De R$ 5.000 a R$ 7.350: Redução linear decrescente
     * 
     * @see Lei 15.270/2025 - Art. 3º-A
     */
    private function calculateMonthlyTax(float $monthlyBase): float
    {
        if ($monthlyBase <= 0) {
            return 0.0;
        }

        // Calcular imposto bruto pela tabela progressiva
        $grossTax = 0.0;
        foreach (self::TAX_BRACKETS_MONTHLY as $bracket) {
            if ($monthlyBase <= $bracket['limit']) {
                $grossTax = max(0, ($monthlyBase * $bracket['rate']) - $bracket['deduction']);
                break;
            }
        }

        // Art. 3º-A - Aplicar redução de imposto
        $reduction = $this->calculateTaxReduction($monthlyBase, $grossTax);

        return max(0, $grossTax - $reduction);
    }

    /**
     * Calcula a redução de imposto conforme Art. 3º-A da Lei 15.270/2025
     * 
     * Fórmula:
     * - Até R$ 5.000: Redução = min(312,89, Imposto Devido)
     * - De R$ 5.000 a R$ 7.350: Redução = 978,62 - (0,133145 × Rendimentos)
     * - Acima de R$ 7.350: Redução = 0
     * 
     * @see Lei 15.270/2025 - Art. 3º-A, §1º
     */
    private function calculateTaxReduction(float $monthlyBase, float $grossTax): float
    {
        // Faixa 1: Até R$ 5.000 - Isenção total
        if ($monthlyBase <= self::TAX_REDUCTION_FULL_LIMIT) {
            return min(self::TAX_REDUCTION_MAX_VALUE, $grossTax);
        }

        // Faixa 2: De R$ 5.000 a R$ 7.350 - Redução linear decrescente
        if ($monthlyBase <= self::TAX_REDUCTION_PHASEOUT_LIMIT) {
            $reduction = self::TAX_REDUCTION_FORMULA_CONSTANT 
                - (self::TAX_REDUCTION_FACTOR * $monthlyBase);
            return max(0, min($reduction, $grossTax));
        }

        // Faixa 3: Acima de R$ 7.350 - Sem redução
        return 0.0;
    }

    /**
     * Gera texto de recomendação para o usuário
     */
    private function generateRecommendation(bool $isSimplifiedBetter, float $savings): string
    {
        if ($savings < 10) {
            return 'Resultados similares em ambos os regimes.';
        }

        $savingsFormatted = 'R$ ' . number_format($savings, 2, ',', '.');

        if ($isSimplifiedBetter) {
            return "O Desconto Simplificado economiza {$savingsFormatted}.";
        }

        return "As Deduções Legais economizam {$savingsFormatted}.";
    }
}

