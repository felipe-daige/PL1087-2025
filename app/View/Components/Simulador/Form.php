<?php

namespace App\View\Components\Simulador;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Form extends Component
{
    public $state;
    public $request;
    public $submitted;
    public $constants;
    public $educationWarning;

    /**
     * Create a new component instance.
     */
    public function __construct($state, $request, $submitted, $constants, $educationWarning = false)
    {
        $this->state = $state;
        $this->request = $request;
        $this->submitted = $submitted;
        $this->constants = $constants;
        $this->educationWarning = $educationWarning;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.simulador.form');
    }
}
