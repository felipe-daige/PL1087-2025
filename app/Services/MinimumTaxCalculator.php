<?php

namespace App\Services;

use App\DTOs\TaxInputData;

/**
 * PRODUTO 2: Simulação IRPFM (Imposto Mínimo / Grandes Fortunas)
 * 
 * Implementa o cálculo do Imposto de Renda Pessoa Física Mínimo
 * conforme Lei 15.270/2025, incluindo a "Trava" corporativa
 * para evitar bitributação.
 * 
 * @see Lei 15.270/2025 - Art. 4º (Imposto Mínimo)
 */
class MinimumTaxCalculator
{
    // ========================================
    // CONSTANTES DA LEI 15.270/2025 - ART. 4º
    // ========================================

    /**
     * Art. 4º - Gatilho mínimo para incidência do IRPFM
     * Contribuintes com renda total acima deste valor estão sujeitos ao imposto mínimo
     */
    private const IRPFM_THRESHOLD_MIN = 600000.00;

    /**
     * Art. 4º - Teto para alíquota progressiva
     * Acima deste valor, a alíquota é fixa em 10%
     */
    private const IRPFM_THRESHOLD_MAX = 1200000.00;

    /**
     * Art. 4º - Alíquota máxima do IRPFM
     */
    private const IRPFM_MAX_RATE = 0.10;

    /**
     * Art. 4º, §1º - Divisor da fórmula de alíquota progressiva
     * Alíquota = (Renda / 60.000) - 10
     */
    private const IRPFM_DIVISOR = 60000.00;

    /**
     * Executa o cálculo do IRPFM
     * 
     * @param TaxInputData $input Dados do contribuinte
     * @param float $traditionalTax Imposto calculado pelo regime tradicional (Produto 1)
     */
    public function calculate(TaxInputData $input, float $traditionalTax): array
    {
        // ========================================
        // PASSO 1: Calcular Renda Total (Base Expandida)
        // Art. 4º, §1º - Inclui tributáveis + isentos + exclusivos
        // ========================================
        $totalIncome = $input->getTotalIncomeForIRPFM();
        
        // Detalhamento por tipo de renda
        $incomeBreakdown = [
            'taxable' => $input->getTotalGrossAnnual() + $input->income13,
            'rental' => $input->getTotalRentalGrossAnnual(),
            'dividends' => $input->exemptIncome->dividendsTotal,
            'jcp' => $input->exemptIncome->jcpTotal,
            'financialInvestments' => $input->exemptIncome->financialInvestments,
            'taxExemptInvestments' => $input->exemptIncome->taxExemptInvestments,
            'fiiDividends' => $input->exemptIncome->fiiDividends,
            'otherExempt' => $input->exemptIncome->otherExempt,
            'total' => $totalIncome,
        ];

        // ========================================
        // PASSO 2: Verificar se atinge o gatilho
        // Art. 4º - R$ 600.000,00/ano
        // ========================================
        $triggered = $totalIncome > self::IRPFM_THRESHOLD_MIN;

        if (!$triggered) {
            return [
                'triggered' => false,
                'totalIncome' => $totalIncome,
                'incomeBreakdown' => $incomeBreakdown,
                'threshold' => self::IRPFM_THRESHOLD_MIN,
                'distanceToThreshold' => self::IRPFM_THRESHOLD_MIN - $totalIncome,
                'minimumRate' => 0,
                'minimumTaxGross' => 0,
                'pjTaxCredit' => 0,
                'traditionalTax' => $traditionalTax,
                'minimumTaxDue' => 0,
                'additionalTaxDue' => 0,
                'effectiveRate' => $totalIncome > 0 ? ($traditionalTax / $totalIncome) * 100 : 0,
                'message' => 'Renda abaixo do gatilho do IRPFM (R$ 600.000/ano).',
            ];
        }

        // ========================================
        // PASSO 3: Calcular Alíquota Mínima
        // Art. 4º, §2º - Progressiva de 0% a 10%
        // ========================================
        $minimumRate = $this->calculateMinimumRate($totalIncome);

        // ========================================
        // PASSO 4: Calcular Imposto Mínimo Bruto
        // ========================================
        $minimumTaxGross = $totalIncome * $minimumRate;

        // ========================================
        // PASSO 5: Calcular Crédito de Imposto PJ (Trava Anti-Bitributação)
        // Art. 4º, §3º - Abatimento de IRPJ/CSLL pagos
        // ========================================
        $pjTaxCredit = $this->calculatePJTaxCredit($input, $minimumTaxGross);

        // ========================================
        // PASSO 6: Calcular Imposto Mínimo Líquido
        // ========================================
        $minimumTaxNet = max(0, $minimumTaxGross - $pjTaxCredit);

        // ========================================
        // PASSO 7: Comparar com Imposto Tradicional
        // O contribuinte paga o MAIOR entre os dois
        // ========================================
        $additionalTaxDue = max(0, $minimumTaxNet - $traditionalTax);

        // Imposto total final
        $totalTaxDue = $traditionalTax + $additionalTaxDue;

        // Alíquota efetiva real
        $effectiveRate = $totalIncome > 0 ? ($totalTaxDue / $totalIncome) * 100 : 0;

        return [
            'triggered' => true,
            'totalIncome' => $totalIncome,
            'incomeBreakdown' => $incomeBreakdown,
            'threshold' => self::IRPFM_THRESHOLD_MIN,
            
            // Cálculo do IRPFM
            'minimumRate' => $minimumRate * 100, // Em percentual
            'minimumTaxGross' => $minimumTaxGross,
            
            // Trava Corporativa
            'pjTaxCredit' => $pjTaxCredit,
            'pjData' => $this->getPJBreakdown($input),
            
            // Comparativo
            'traditionalTax' => $traditionalTax,
            'minimumTaxNet' => $minimumTaxNet,
            'additionalTaxDue' => $additionalTaxDue,
            'totalTaxDue' => $totalTaxDue,
            
            // Métricas
            'effectiveRate' => $effectiveRate,
            'targetRate' => $minimumRate * 100,
            'rateGap' => max(0, ($minimumRate * 100) - $effectiveRate),
            
            // Mensagem explicativa
            'message' => $this->generateMessage($additionalTaxDue, $pjTaxCredit),
            
            // Constantes para referência
            'constants' => [
                'thresholdMin' => self::IRPFM_THRESHOLD_MIN,
                'thresholdMax' => self::IRPFM_THRESHOLD_MAX,
                'maxRate' => self::IRPFM_MAX_RATE * 100,
            ],
        ];
    }

    /**
     * Calcula a alíquota mínima conforme Art. 4º da Lei 15.270/2025
     * 
     * Fórmula:
     * - Renda ≤ R$ 600k: 0%
     * - R$ 600k < Renda ≤ R$ 1.2M: Alíquota = (Renda / 60.000) - 10
     * - Renda > R$ 1.2M: 10%
     * 
     * @see Lei 15.270/2025 - Art. 4º, §2º
     */
    private function calculateMinimumRate(float $totalIncome): float
    {
        // Abaixo do gatilho: 0%
        if ($totalIncome <= self::IRPFM_THRESHOLD_MIN) {
            return 0.0;
        }

        // Faixa progressiva: entre R$ 600k e R$ 1.2M
        // Art. 4º, §2º: Alíquota = (Renda / 60.000) - 10
        if ($totalIncome <= self::IRPFM_THRESHOLD_MAX) {
            $ratePercentage = ($totalIncome / self::IRPFM_DIVISOR) - 10;
            return max(0, $ratePercentage / 100); // Converter para decimal
        }

        // Acima de R$ 1.2M: Alíquota fixa de 10%
        return self::IRPFM_MAX_RATE;
    }

    /**
     * Calcula o crédito de imposto PJ (Trava Anti-Bitributação)
     * 
     * O crédito é proporcional ao lucro distribuído em relação ao lucro contábil,
     * limitado ao valor do IRPFM bruto.
     * 
     * Fórmula: Crédito = min(IRPJ + CSLL × proporção, IRPFM Bruto)
     * 
     * @see Lei 15.270/2025 - Art. 4º, §3º
     */
    private function calculatePJTaxCredit(TaxInputData $input, float $minimumTaxGross): float
    {
        // Sem dados corporativos, não há crédito
        if ($input->corporateData === null || !$input->corporateData->hasData()) {
            return 0.0;
        }

        // Calcular crédito proporcional
        $proportionalCredit = $input->corporateData->getProportionalTaxCredit();

        // O crédito não pode exceder o IRPFM bruto
        // Art. 4º, §3º - Limitação do abatimento
        return min($proportionalCredit, $minimumTaxGross);
    }

    /**
     * Retorna o detalhamento dos dados PJ para exibição
     */
    private function getPJBreakdown(TaxInputData $input): ?array
    {
        if ($input->corporateData === null || !$input->corporateData->hasData()) {
            return null;
        }

        $corporate = $input->corporateData;

        return [
            'accountingProfit' => $corporate->accountingProfit,
            'distributedProfit' => $corporate->distributedProfit,
            'distributionRatio' => $corporate->getDistributionRatio() * 100,
            'irpjPaid' => $corporate->irpjPaid,
            'csllPaid' => $corporate->csllPaid,
            'totalCorporateTax' => $corporate->getTotalCorporateTax(),
            'ownershipPercentage' => $corporate->ownershipPercentage,
            'proportionalCredit' => $corporate->getProportionalTaxCredit(),
        ];
    }

    /**
     * Gera mensagem explicativa do resultado
     */
    private function generateMessage(float $additionalTaxDue, float $pjTaxCredit): string
    {
        if ($additionalTaxDue <= 0) {
            if ($pjTaxCredit > 0) {
                return 'Imposto tradicional + crédito PJ atingem a carga mínima. Sem complemento devido.';
            }
            return 'Imposto tradicional já atinge a carga mínima exigida.';
        }

        $formatted = 'R$ ' . number_format($additionalTaxDue, 2, ',', '.');
        
        if ($pjTaxCredit > 0) {
            $creditFormatted = 'R$ ' . number_format($pjTaxCredit, 2, ',', '.');
            return "Após crédito PJ de {$creditFormatted}, complemento de IRPFM: {$formatted}.";
        }

        return "Complemento de IRPFM devido: {$formatted}.";
    }

    /**
     * Simula cenários de otimização do IRPFM
     * Útil para planejamento tributário
     */
    public function simulateOptimization(TaxInputData $input, float $traditionalTax): array
    {
        $result = $this->calculate($input, $traditionalTax);
        $scenarios = [];

        // Cenário 1: Aumentar distribuição de lucros (se PJ existir)
        if ($input->corporateData !== null && $input->corporateData->hasData()) {
            $corporate = $input->corporateData;
            $maxCredit = $corporate->getTotalCorporateTax() * ($corporate->ownershipPercentage / 100);
            
            if ($maxCredit > $result['pjTaxCredit']) {
                $scenarios['increaseDistribution'] = [
                    'description' => 'Distribuir 100% do lucro contábil',
                    'additionalCredit' => $maxCredit - $result['pjTaxCredit'],
                    'potentialSavings' => min($maxCredit - $result['pjTaxCredit'], $result['additionalTaxDue']),
                ];
            }
        }

        // Cenário 2: Reduzir renda para abaixo do gatilho
        if ($result['triggered']) {
            $excess = $result['totalIncome'] - self::IRPFM_THRESHOLD_MIN;
            $scenarios['reduceIncome'] = [
                'description' => 'Diferir renda para ano seguinte',
                'excessAmount' => $excess,
                'potentialSavings' => $result['additionalTaxDue'],
            ];
        }

        return [
            'current' => $result,
            'scenarios' => $scenarios,
        ];
    }
}

