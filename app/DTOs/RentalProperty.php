<?php

namespace App\DTOs;

/**
 * DTO para representar um imóvel de aluguel
 * 
 * Utilizado para:
 * - Cálculo de renda tributável PF (deduzindo taxa adm e IPTU)
 * - Comparativo PF vs PJ (Holding Patrimonial)
 * 
 * @see Lei 15.270/2025 - Produto 3 (Planejamento Tributário)
 */
class RentalProperty
{
    public function __construct(
        public readonly string $name,
        public readonly float $grossMonthly,
        public readonly float $adminFee,      // Taxa de administração (dedutível PF)
        public readonly float $iptuMonthly,   // IPTU rateado (dedutível se pago pelo dono)
        public readonly float $condoFee = 0,   // Condomínio (dedutível se pago pelo dono)
        public readonly string $periodicity = 'monthly'  // 'monthly' ou 'annual'
    ) {}

    /**
     * Cria instância a partir de array do formulário
     */
    public static function fromArray(array $data): self
    {
        $periodicity = $data['periodicity'] ?? 'monthly';
        if (!in_array($periodicity, ['monthly', 'annual'])) {
            $periodicity = 'monthly';
        }
        
        return new self(
            name: $data['name'] ?? '',
            grossMonthly: self::parseFloat($data['gross'] ?? 0),
            adminFee: self::parseFloat($data['admin_fee'] ?? 0),
            iptuMonthly: self::parseFloat($data['iptu'] ?? 0),
            condoFee: self::parseFloat($data['condo'] ?? 0),
            periodicity: $periodicity
        );
    }

    /**
     * Retorna o valor bruto mensal (convertendo se necessário)
     */
    public function getGrossMonthly(): float
    {
        if ($this->periodicity === 'annual') {
            return $this->grossMonthly / 12;
        }
        return $this->grossMonthly;
    }

    /**
     * Retorna o valor bruto anual (convertendo se necessário)
     */
    public function getGrossAnnual(): float
    {
        if ($this->periodicity === 'annual') {
            return $this->grossMonthly;
        }
        return $this->grossMonthly * 12;
    }

    /**
     * Retorna a taxa de administração mensal (convertendo se necessário)
     */
    public function getAdminFeeMonthly(): float
    {
        if ($this->periodicity === 'annual') {
            return $this->adminFee / 12;
        }
        return $this->adminFee;
    }

    /**
     * Retorna o IPTU mensal (convertendo se necessário)
     */
    public function getIptuMonthly(): float
    {
        if ($this->periodicity === 'annual') {
            return $this->iptuMonthly / 12;
        }
        return $this->iptuMonthly;
    }

    /**
     * Retorna o condomínio mensal (convertendo se necessário)
     */
    public function getCondoMonthly(): float
    {
        if ($this->periodicity === 'annual') {
            return $this->condoFee / 12;
        }
        return $this->condoFee;
    }

    /**
     * Calcula o rendimento líquido mensal (base tributável PF)
     * Taxa de administração e IPTU/Condomínio são dedutíveis quando pagos pelo proprietário
     */
    public function getNetMonthlyPF(): float
    {
        $gross = $this->getGrossMonthly();
        $adminFee = $this->getAdminFeeMonthly();
        $iptu = $this->getIptuMonthly();
        $condo = $this->getCondoMonthly();
        
        return max(0, $gross - $adminFee - $iptu - $condo);
    }

    /**
     * Calcula o rendimento líquido anual PF
     */
    public function getNetAnnualPF(): float
    {
        if ($this->periodicity === 'annual') {
            // Se os valores são anuais, calcular direto
            $gross = $this->grossMonthly;
            $adminFee = $this->adminFee;
            $iptu = $this->iptuMonthly;
            $condo = $this->condoFee;
            
            return max(0, $gross - $adminFee - $iptu - $condo);
        }
        
        // Se mensal, multiplicar por 12
        return $this->getNetMonthlyPF() * 12;
    }

    /**
     * Calcula o total de despesas dedutíveis mensais
     */
    public function getDeductibleExpenses(): float
    {
        return $this->getAdminFeeMonthly() + $this->getIptuMonthly() + $this->getCondoMonthly();
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
            'name' => $this->name,
            'gross' => $this->grossMonthly,
            'admin_fee' => $this->adminFee,
            'iptu' => $this->iptuMonthly,
            'condo' => $this->condoFee,
            'periodicity' => $this->periodicity,
        ];
    }
}

