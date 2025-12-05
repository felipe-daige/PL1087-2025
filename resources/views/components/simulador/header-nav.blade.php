@php
    $isCalculadora = request()->routeIs('simulador.index') || request()->routeIs('simulador.store') || request()->routeIs('simulador.resultado');
    $isGuia = request()->routeIs('simulador.guia');
@endphp

<div class="border-t border-neutral-200">
    <nav class="flex gap-1">
        <a href="{{ route('simulador.index') }}" 
           class="nav-tab px-6 py-3 text-sm font-medium border-b-2 transition-colors {{ $isCalculadora ? 'text-blue-600 border-blue-500' : 'text-neutral-500 border-transparent hover:text-blue-600 hover:border-blue-300' }}">
            Calculadora
        </a>
        <a href="{{ route('simulador.guia') }}" 
           class="nav-tab px-6 py-3 text-sm font-medium border-b-2 transition-colors {{ $isGuia ? 'text-blue-600 border-blue-500' : 'text-neutral-500 border-transparent hover:text-blue-600 hover:border-blue-300' }}">
            Guia da Lei
        </a>
    </nav>
</div>
