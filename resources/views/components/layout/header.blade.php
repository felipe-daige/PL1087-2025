<header class="bg-white border-b border-neutral-200 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-16 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-sm">$</div>
                <div>
                    <h1 class="text-xl font-bold text-neutral-800 tracking-tight">Simulador IRPF 2026</h1>
                    <p class="text-xs text-neutral-500 hidden sm:block">Baseado na Lei 15.270/2025 (MVP)</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center px-3 py-1 bg-neutral-100 rounded-full border border-neutral-200">
                    <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span>
                    <span class="text-xs font-medium text-neutral-600">Salvo automaticamente</span>
                </div>
                @if($submitted)
                <button type="button" onclick="resetApp()" class="text-sm text-brand-700 hover:text-brand-800 font-medium transition-colors">
                    Reiniciar
                </button>
                @endif
            </div>
        </div>
        @isset($nav)
            {{ $nav }}
        @else
            {{ $slot }}
        @endisset
    </div>
</header>
