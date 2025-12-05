<?php

namespace App\DTOs;

/**
 * DTO para dados corporativos/PJ do contribuinte
 * 
 * CRÍTICO para o cálculo da "Trava" do IRPFM (Anti-Bitributação)
 * 
 * A Lei 15.270/2025 permite abater o IRPJ/CSLL pago pela empresa
 * do IRPFM devido na pessoa física, evitando bitributação excessiva.
 * 
 * @see Lei 15.270/2025 - Art. 4º (Imposto Mínimo - Crédito Corporativo)
 */
class CorporateData
{
    public function __construct(
        public readonly float $accountingProfit,    // Lucro contábil da empresa
        public readonly float $distributedProfit,   // Lucro efetivamente distribuído ao sócio
        public readonly float $irpjPaid,            // IRPJ pago pela empresa
        public readonly float $csllPaid,            // CSLL paga pela empresa
        public readonly float $ownershipPercentage = 100.0  // % de participação do sócio
    ) {}

    /**
     * Cria instância a partir de array do formulário
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accountingProfit: self::parseFloat($data['accounting_profit'] ?? 0),
            distributedProfit: self::parseFloat($data['distributed_profit'] ?? 0),
            irpjPaid: self::parseFloat($data['irpj_paid'] ?? 0),
            csllPaid: self::parseFloat($data['csll_paid'] ?? 0),
            ownershipPercentage: self::parseFloat($data['ownership'] ?? 100)
        );
    }

    /**
     * Calcula o total de imposto pago pela PJ (IRPJ + CSLL)
     */
    public function getTotalCorporateTax(): float
    {
        return $this->irpjPaid + $this->csllPaid;
    }

    /**
     * Calcula a proporção do lucro distribuído em relação ao lucro contábil
     * Usado para calcular o crédito proporcional de IRPJ/CSLL
     * 
     * @see Lei 15.270/2025 - Art. 4º, §2º
     */
    public function getDistributionRatio(): float
    {
        if ($this->accountingProfit <= 0) {
            return 0.0;
        }

        return min(1.0, $this->distributedProfit / $this->accountingProfit);
    }

    /**
     * Calcula o crédito de imposto PJ proporcional ao lucro distribuído
     * Este valor pode ser abatido do IRPFM (Trava Anti-Bitributação)
     * 
     * Fórmula: crédito = (IRPJ + CSLL) × (lucro distribuído / lucro contábil) × % participação
     * 
     * @see Lei 15.270/2025 - Art. 4º, §2º
     */
    public function getProportionalTaxCredit(): float
    {
        $totalTax = $this->getTotalCorporateTax();
        $ratio = $this->getDistributionRatio();
        $ownership = $this->ownershipPercentage / 100;

        return $totalTax * $ratio * $ownership;
    }

    /**
     * Verifica se há dados corporativos válidos
     */
    public function hasData(): bool
    {
        return $this->accountingProfit > 0 || $this->distributedProfit > 0;
    }

    /**
     * Converte valor para float (formato brasileiro)
     */
    private static function parseFloat(mixed $value): float
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
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'accounting_profit' => $this->accountingProfit,
            'distributed_profit' => $this->distributedProfit,
            'irpj_paid' => $this->irpjPaid,
            'csll_paid' => $this->csllPaid,
            'ownership' => $this->ownershipPercentage,
        ];
    }
}

