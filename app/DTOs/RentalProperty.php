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
        public readonly float $condoFee = 0   // Condomínio (dedutível se pago pelo dono)
    ) {}

    /**
     * Cria instância a partir de array do formulário
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            grossMonthly: self::parseFloat($data['gross'] ?? 0),
            adminFee: self::parseFloat($data['admin_fee'] ?? 0),
            iptuMonthly: self::parseFloat($data['iptu'] ?? 0),
            condoFee: self::parseFloat($data['condo'] ?? 0)
        );
    }

    /**
     * Calcula o rendimento líquido mensal (base tributável PF)
     * Taxa de administração e IPTU/Condomínio são dedutíveis quando pagos pelo proprietário
     */
    public function getNetMonthlyPF(): float
    {
        return max(0, $this->grossMonthly - $this->adminFee - $this->iptuMonthly - $this->condoFee);
    }

    /**
     * Calcula a receita bruta anual (para cálculo PJ)
     */
    public function getGrossAnnual(): float
    {
        return $this->grossMonthly * 12;
    }

    /**
     * Calcula o rendimento líquido anual PF
     */
    public function getNetAnnualPF(): float
    {
        return $this->getNetMonthlyPF() * 12;
    }

    /**
     * Calcula o total de despesas dedutíveis mensais
     */
    public function getDeductibleExpenses(): float
    {
        return $this->adminFee + $this->iptuMonthly + $this->condoFee;
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
        ];
    }
}

