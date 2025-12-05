<?php

namespace App\View\Components\Layout;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Header extends Component
{
    public $submitted;

    /**
     * Create a new component instance.
     */
    public function __construct($submitted = false)
    {
        $this->submitted = $submitted;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.layout.header');
    }
}
