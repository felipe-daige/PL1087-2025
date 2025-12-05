<?php

namespace App\DTOs;

/**
 * DTO para rendimentos isentos e de tributação exclusiva
 * 
 * CRÍTICO para o cálculo do IRPFM - A base de cálculo do Imposto Mínimo
 * inclui rendimentos que são isentos na tributação tradicional.
 * 
 * @see Lei 15.270/2025 - Art. 4º (Base de cálculo expandida do IRPFM)
 * @see Lei 15.270/2025 - Art. 5º (Tributação de dividendos)
 */
class ExemptIncome
{
    public function __construct(
        public readonly float $dividendsTotal,           // Total de dividendos recebidos no ano
        public readonly float $dividendsExcess,          // Valor que excedeu R$ 50k/mês (tributado 10%)
        public readonly float $jcpTotal,                 // Juros sobre Capital Próprio (tributado 15% na fonte)
        public readonly float $financialInvestments,     // Aplicações financeiras (renda fixa, fundos, etc)
        public readonly float $taxExemptInvestments = 0, // LCI, LCA, CRI, CRA (isentos de IR)
        public readonly float $fiiDividends = 0,         // Dividendos de FIIs (isentos para PF)
        public readonly float $otherExempt = 0           // Outros rendimentos isentos
    ) {}

    /**
     * Cria instância a partir de array do formulário
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dividendsTotal: self::parseFloat($data['dividends_total'] ?? 0),
            dividendsExcess: self::parseFloat($data['dividends_excess'] ?? 0),
            jcpTotal: self::parseFloat($data['jcp_total'] ?? 0),
            financialInvestments: self::parseFloat($data['financial_investments'] ?? 0),
            taxExemptInvestments: self::parseFloat($data['tax_exempt_investments'] ?? 0),
            fiiDividends: self::parseFloat($data['fii_dividends'] ?? 0),
            otherExempt: self::parseFloat($data['other_exempt'] ?? 0)
        );
    }

    /**
     * Calcula o total de rendimentos isentos (para IRPFM)
     * Inclui dividendos até o limite de isenção, LCI/LCA, FIIs, etc.
     */
    public function getTotalExemptIncome(): float
    {
        // Dividendos isentos = total - excesso tributado
        $exemptDividends = max(0, $this->dividendsTotal - $this->dividendsExcess);
        
        return $exemptDividends 
            + $this->taxExemptInvestments 
            + $this->fiiDividends 
            + $this->otherExempt;
    }

    /**
     * Calcula o total de rendimentos de tributação exclusiva
     * JCP (15%), Dividendos excedentes (10%), Aplicações financeiras
     */
    public function getTotalExclusiveIncome(): float
    {
        return $this->dividendsExcess 
            + $this->jcpTotal 
            + $this->financialInvestments;
    }

    /**
     * Calcula o imposto sobre dividendos excedentes
     * 
     * @see Lei 15.270/2025 - Art. 5º (10% sobre excedente de R$ 50k/mês)
     */
    public function getDividendTax(): float
    {
        // Art. 5º - Alíquota de 10% sobre dividendos que excedem R$ 50.000/mês
        return $this->dividendsExcess * 0.10;
    }

    /**
     * Calcula o imposto sobre JCP (retido na fonte)
     * Alíquota fixa de 15%
     */
    public function getJcpTax(): float
    {
        return $this->jcpTotal * 0.15;
    }

    /**
     * Calcula o total para base do IRPFM
     * Soma todos os rendimentos (isentos + exclusivos)
     * 
     * @see Lei 15.270/2025 - Art. 4º, §1º
     */
    public function getTotalForIRPFM(): float
    {
        return $this->dividendsTotal 
            + $this->jcpTotal 
            + $this->financialInvestments 
            + $this->taxExemptInvestments 
            + $this->fiiDividends 
            + $this->otherExempt;
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
            'dividends_total' => $this->dividendsTotal,
            'dividends_excess' => $this->dividendsExcess,
            'jcp_total' => $this->jcpTotal,
            'financial_investments' => $this->financialInvestments,
            'tax_exempt_investments' => $this->taxExemptInvestments,
            'fii_dividends' => $this->fiiDividends,
            'other_exempt' => $this->otherExempt,
        ];
    }
}

