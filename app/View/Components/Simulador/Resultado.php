<?php

namespace App\View\Components\Simulador;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Resultado extends Component
{
    public $displayFinalResult;
    public $resultLabel;
    public $resultClass;
    public $cardBorder;
    public $effectiveRate;
    public $chartData;
    public $alerts;
    public $grossTaxable;
    public $simplifiedDiscount;
    public $totalDeductions;
    public $dividendTax;
    public $totalTaxLiability;
    public $taxPaid;
    public $recommendationText;
    public $taxCalculatorService;
    public $isNegativeFinalResult;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $displayFinalResult,
        $resultLabel,
        $resultClass,
        $cardBorder,
        $effectiveRate,
        $chartData,
        $alerts,
        $grossTaxable,
        $simplifiedDiscount,
        $totalDeductions,
        $dividendTax,
        $totalTaxLiability,
        $taxPaid,
        $recommendationText,
        $taxCalculatorService,
        $isNegativeFinalResult = false
    ) {
        $this->displayFinalResult = $displayFinalResult;
        $this->resultLabel = $resultLabel;
        $this->resultClass = $resultClass;
        $this->cardBorder = $cardBorder;
        $this->effectiveRate = $effectiveRate;
        $this->chartData = $chartData;
        $this->alerts = $alerts;
        $this->grossTaxable = $grossTaxable;
        $this->simplifiedDiscount = $simplifiedDiscount;
        $this->totalDeductions = $totalDeductions;
        $this->dividendTax = $dividendTax;
        $this->totalTaxLiability = $totalTaxLiability;
        $this->taxPaid = $taxPaid;
        $this->recommendationText = $recommendationText;
        $this->taxCalculatorService = $taxCalculatorService;
        $this->isNegativeFinalResult = $isNegativeFinalResult;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.simulador.resultado');
    }
}
