<?php

namespace App\DTOs;

/**
 * DTO para representar uma fonte de renda tributável
 * 
 * Utilizado para calcular:
 * - Teto global do INSS (múltiplas fontes)
 * - IRRF retido na fonte
 * - Base de cálculo do IRPF progressivo
 * 
 * @see Lei 15.270/2025 - Art. 3º-A (Tabela Progressiva)
 */
class IncomeSource
{
    public function __construct(
        public readonly string $name,
        public readonly float $grossMonthly,
        public readonly float $inssWithheld,
        public readonly float $irrfWithheld,
        public readonly string $type = 'salary' // salary, prolabore, autonomous, retirement
    ) {}

    /**
     * Cria instância a partir de array do formulário
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            grossMonthly: self::parseFloat($data['gross'] ?? 0),
            inssWithheld: self::parseFloat($data['inss'] ?? 0),
            irrfWithheld: self::parseFloat($data['irrf'] ?? 0),
            type: $data['type'] ?? 'salary'
        );
    }

    /**
     * Calcula o rendimento líquido mensal (após INSS)
     */
    public function getNetMonthly(): float
    {
        return $this->grossMonthly - $this->inssWithheld;
    }

    /**
     * Calcula o rendimento bruto anual
     */
    public function getGrossAnnual(): float
    {
        return $this->grossMonthly * 12;
    }

    /**
     * Calcula o INSS retido anual
     */
    public function getInssAnnual(): float
    {
        return $this->inssWithheld * 12;
    }

    /**
     * Calcula o IRRF retido anual
     */
    public function getIrrfAnnual(): float
    {
        return $this->irrfWithheld * 12;
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
            'inss' => $this->inssWithheld,
            'irrf' => $this->irrfWithheld,
            'type' => $this->type,
        ];
    }
}

