<?php

namespace App\Http\Controllers;

use App\Services\TaxCalculatorService;
use Illuminate\Http\Request;

class TaxCalculatorController extends Controller
{
    protected TaxCalculatorService $taxCalculatorService;

    public function __construct(TaxCalculatorService $taxCalculatorService)
    {
        $this->taxCalculatorService = $taxCalculatorService;
    }

    /**
     * Exibe a calculadora de IRPF
     */
    public function index(Request $request)
    {
        $data = $this->taxCalculatorService->calculate($request);
        $data['taxCalculatorService'] = $this->taxCalculatorService;
        return view('simulador.calculadora', $data);
    }

    /**
     * Processa o formulário e redireciona para página de resultados
     */
    public function store(Request $request)
    {
        $data = $this->taxCalculatorService->calculate($request);
        
        // Remover dados não serializáveis antes de salvar na sessão
        $sessionData = collect($data)->except(['request', 'inputData'])->toArray();
        
        // Salvar dados serializáveis na sessão para a página de resultados
        session()->put('resultado_irpf', $sessionData);
        session()->put('resultado_timestamp', now());
        
        return redirect()->route('simulador.resultado');
    }

    /**
     * Exibe a página de resultados com gráficos e informações detalhadas
     */
    public function result()
    {
        // Verificar se existem dados na sessão
        $data = session()->get('resultado_irpf');
        
        if (!$data) {
            return redirect()->route('simulador.index')
                ->with('warning', 'Por favor, preencha o formulário para ver os resultados.');
        }
        
        $data['taxCalculatorService'] = $this->taxCalculatorService;
        
        return view('simulador.resultado', $data);
    }

    /**
     * Exibe o guia da Lei 15.270/2025
     */
    public function guide()
    {
        return view('simulador.guia');
    }

    /**
     * API endpoint para cálculos AJAX (opcional)
     */
    public function calculateApi(Request $request)
    {
        $data = $this->taxCalculatorService->calculate($request);
        
        return response()->json([
            'success' => true,
            'produtos' => $data['produtos'] ?? [],
            'consolidated' => $data['consolidated'] ?? [],
            'chartData' => $data['chartData'] ?? [],
            'alerts' => $data['alerts'] ?? [],
        ]);
    }
}
