<?php

namespace App\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * DTO agregador principal para todos os dados de entrada do sistema
 * 
 * Centraliza todos os inputs necessários para calcular:
 * - Produto 1: Comparativo Simplificada vs Completa
 * - Produto 2: IRPFM (Imposto Mínimo)
 * - Produto 3: Comparativo Aluguéis PF vs PJ
 * 
 * @see Lei 15.270/2025
 */
class TaxInputData
{
    /**
     * @param string|null $birthDate Data de nascimento no formato YYYY-MM-DD (para isenção 65+)
     * @param bool $hasSeriousIllness Possui moléstia grave (isenção total)
     * @param IncomeSource[] $incomeSources Fontes de renda tributável
     * @param RentalProperty[] $rentalProperties Imóveis de aluguel
     * @param float $income13 13º salário
     * @param float $deductionHealth Gastos com saúde
     * @param float $deductionEducation Gastos com educação
     * @param float $deductionPGBL Previdência privada PGBL
     * @param int $dependents Número de dependentes
     * @param ExemptIncome $exemptIncome Rendimentos isentos/exclusivos
     * @param CorporateData|null $corporateData Dados da PJ (para Trava IRPFM)
     * @param float $taxPaidOther Outros impostos já pagos (carnê-leão)
     * @param float $bookExpenses Despesas de livro caixa (reduz receita bruta de autônomos)
     * @param float $alimonyPaid Pensão alimentícia judicial (dedução legal integral)
     */
    public function __construct(
        public readonly ?string $birthDate,
        public readonly bool $hasSeriousIllness,
        public readonly array $incomeSources,
        public readonly array $rentalProperties,
        public readonly float $income13,
        public readonly float $deductionHealth,
        public readonly float $deductionEducation,
        public readonly float $deductionPGBL,
        public readonly int $dependents,
        public readonly ExemptIncome $exemptIncome,
        public readonly ?CorporateData $corporateData,
        public readonly float $taxPaidOther = 0,
        public readonly float $bookExpenses = 0,
        public readonly float $alimonyPaid = 0
    ) {}

    /**
     * Cria instância a partir do Request do formulário
     */
    public static function fromRequest(Request $request): self
    {
        // Processar fontes de renda
        $incomeSources = [];
        $rawSources = $request->input('incomeSources', []);
        if (is_array($rawSources)) {
            foreach ($rawSources as $source) {
                if (!empty($source) && (($source['gross'] ?? 0) > 0 || ($source['name'] ?? '') !== '')) {
                    $incomeSources[] = IncomeSource::fromArray($source);
                }
            }
        }

        // Processar imóveis de aluguel
        $rentalProperties = [];
        $rawProperties = $request->input('rentalProperties', []);
        if (is_array($rawProperties)) {
            foreach ($rawProperties as $property) {
                if (!empty($property) && (($property['gross'] ?? 0) > 0 || ($property['name'] ?? '') !== '')) {
                    $rentalProperties[] = RentalProperty::fromArray($property);
                }
            }
        }

        // Processar rendimentos isentos
        $exemptIncome = ExemptIncome::fromArray([
            'dividends_total' => $request->input('dividendsTotal'),
            'dividends_excess' => $request->input('dividendsExcess'),
            'jcp_total' => $request->input('jcpTotal'),
            'financial_investments' => $request->input('financialInvestments'),
            'tax_exempt_investments' => $request->input('taxExemptInvestments'),
            'fii_dividends' => $request->input('fiiDividends'),
            'other_exempt' => $request->input('otherExempt'),
        ]);

        // Processar dados corporativos (se fornecidos)
        $corporateData = null;
        if ($request->filled('accountingProfit') || $request->filled('distributedProfit')) {
            $corporateData = CorporateData::fromArray([
                'accounting_profit' => $request->input('accountingProfit'),
                'distributed_profit' => $request->input('distributedProfit'),
                'irpj_paid' => $request->input('irpjPaid'),
                'csll_paid' => $request->input('csllPaid'),
                'ownership' => $request->input('ownershipPercentage', 100),
            ]);
        }

        // Processar data de nascimento (compatibilidade com birthYear antigo)
        $birthDate = null;
        if ($request->filled('birthDate')) {
            $birthDate = $request->input('birthDate');
        } elseif ($request->filled('birthYear')) {
            // Retrocompatibilidade: se receber apenas o ano, assumir 01/01/YYYY
            $year = (int) $request->input('birthYear');
            $birthDate = sprintf('%04d-01-01', $year);
        }

        return new self(
            birthDate: $birthDate,
            hasSeriousIllness: $request->boolean('seriousIllness'),
            incomeSources: $incomeSources,
            rentalProperties: $rentalProperties,
            income13: self::parseFloat($request->input('income13')),
            deductionHealth: self::parseFloat($request->input('deductionHealth')),
            deductionEducation: self::parseFloat($request->input('deductionEducation')),
            deductionPGBL: self::parseFloat($request->input('deductionPGBL')),
            dependents: max(0, (int) $request->input('dependents', 0)),
            exemptIncome: $exemptIncome,
            corporateData: $corporateData,
            taxPaidOther: self::parseFloat($request->input('taxPaid')),
            bookExpenses: self::parseFloat($request->input('bookExpenses')),
            alimonyPaid: self::parseFloat($request->input('alimonyPaid'))
        );
    }

    // ========================================
    // TOTALIZADORES - RENDIMENTOS TRIBUTÁVEIS
    // ========================================

    /**
     * Total bruto mensal de todas as fontes de renda tributável
     */
    public function getTotalGrossMonthly(): float
    {
        return array_reduce(
            $this->incomeSources,
            fn(float $total, IncomeSource $source) => $total + $source->grossMonthly,
            0.0
        );
    }

    /**
     * Total bruto anual de todas as fontes de renda tributável
     */
    public function getTotalGrossAnnual(): float
    {
        return $this->getTotalGrossMonthly() * 12;
    }

    /**
     * Total bruto anual apenas de renda autônoma
     * Usado para limitar a dedução do Livro Caixa (que só pode abater receita de autônomo)
     */
    public function getTotalAutonomousAnnual(): float
    {
        return array_reduce(
            $this->incomeSources,
            fn(float $total, IncomeSource $source) => 
                $source->type === 'autonomous' 
                    ? $total + $source->getGrossAnnual() 
                    : $total,
            0.0
        );
    }

    /**
     * Total de INSS retido (todas as fontes)
     */
    public function getTotalInssWithheld(): float
    {
        return array_reduce(
            $this->incomeSources,
            fn(float $total, IncomeSource $source) => $total + $source->inssWithheld,
            0.0
        );
    }

    /**
     * Total de IRRF retido (todas as fontes)
     */
    public function getTotalIrrfWithheld(): float
    {
        return array_reduce(
            $this->incomeSources,
            fn(float $total, IncomeSource $source) => $total + $source->irrfWithheld,
            0.0
        );
    }

    // ========================================
    // TOTALIZADORES - ALUGUÉIS
    // ========================================

    /**
     * Total bruto mensal de aluguéis (considerando periodicidade)
     */
    public function getTotalRentalGrossMonthly(): float
    {
        return array_reduce(
            $this->rentalProperties,
            fn(float $total, RentalProperty $property) => $total + $property->getGrossMonthly(),
            0.0
        );
    }

    /**
     * Total líquido mensal de aluguéis (após deduções PF)
     * Já considera periodicidade através de getNetMonthlyPF()
     */
    public function getTotalRentalNetMonthly(): float
    {
        return array_reduce(
            $this->rentalProperties,
            fn(float $total, RentalProperty $property) => $total + $property->getNetMonthlyPF(),
            0.0
        );
    }

    /**
     * Total bruto anual de aluguéis (considerando periodicidade)
     */
    public function getTotalRentalGrossAnnual(): float
    {
        return array_reduce(
            $this->rentalProperties,
            fn(float $total, RentalProperty $property) => $total + $property->getGrossAnnual(),
            0.0
        );
    }

    // ========================================
    // TOTALIZADORES - PARA IRPFM
    // ========================================

    /**
     * Calcula a renda total anual para base do IRPFM
     * Inclui: Tributável + Aluguéis + Isentos + Exclusivos
     * 
     * @see Lei 15.270/2025 - Art. 4º, §1º
     */
    public function getTotalIncomeForIRPFM(): float
    {
        $taxableIncome = $this->getTotalGrossAnnual() + $this->income13;
        $rentalIncome = $this->getTotalRentalGrossAnnual();
        $exemptIncome = $this->exemptIncome->getTotalForIRPFM();

        return $taxableIncome + $rentalIncome + $exemptIncome;
    }

    // ========================================
    // TOTALIZADORES - IMPOSTOS JÁ PAGOS
    // ========================================

    /**
     * Total de imposto já pago/retido (para cálculo do saldo)
     */
    public function getTotalTaxPaid(): float
    {
        $irrfAnnual = $this->getTotalIrrfWithheld() * 12;
        $dividendTax = $this->exemptIncome->getDividendTax();
        $jcpTax = $this->exemptIncome->getJcpTax();

        return $irrfAnnual + $dividendTax + $jcpTax + $this->taxPaidOther;
    }

    /**
     * Calcula a idade do contribuinte a partir da data de nascimento completa
     * Considera mês e dia para determinar se já completou aniversário no ano atual
     */
    public function getAge(?Carbon $currentDate = null): int
    {
        if ($this->birthDate === null) {
            return 0;
        }

        try {
            $birthDate = Carbon::parse($this->birthDate);
            $currentDate = $currentDate ?? Carbon::now();
            
            return $birthDate->diffInYears($currentDate);
        } catch (\Exception $e) {
            // Se houver erro ao parsear a data, retornar 0
            return 0;
        }
    }

    /**
     * Verifica se é elegível para isenção de 65+ anos
     */
    public function isEligibleForSeniorExemption(?Carbon $currentDate = null): bool
    {
        return $this->getAge($currentDate) >= 65;
    }

    /**
     * Verifica se atinge o gatilho do IRPFM (R$ 600k/ano)
     * 
     * @see Lei 15.270/2025 - Art. 4º
     */
    public function triggersIRPFM(): bool
    {
        return $this->getTotalIncomeForIRPFM() > 600000.00;
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
     * Converte para array (para debug/serialização)
     */
    public function toArray(): array
    {
        return [
            'birthDate' => $this->birthDate,
            'hasSeriousIllness' => $this->hasSeriousIllness,
            'incomeSources' => array_map(fn($s) => $s->toArray(), $this->incomeSources),
            'rentalProperties' => array_map(fn($p) => $p->toArray(), $this->rentalProperties),
            'income13' => $this->income13,
            'deductionHealth' => $this->deductionHealth,
            'deductionEducation' => $this->deductionEducation,
            'deductionPGBL' => $this->deductionPGBL,
            'dependents' => $this->dependents,
            'exemptIncome' => $this->exemptIncome->toArray(),
            'corporateData' => $this->corporateData?->toArray(),
            'taxPaidOther' => $this->taxPaidOther,
            'bookExpenses' => $this->bookExpenses,
            'alimonyPaid' => $this->alimonyPaid,
        ];
    }
}

