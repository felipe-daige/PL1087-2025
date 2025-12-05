<?php

use App\Http\Controllers\TaxCalculatorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('simulador.index');
});

Route::prefix('simulador')->name('simulador.')->group(function () {
    Route::get('/', [TaxCalculatorController::class, 'index'])->name('index');
    Route::post('/', [TaxCalculatorController::class, 'store'])->name('store');
    Route::get('/resultado', [TaxCalculatorController::class, 'result'])->name('resultado');
    Route::get('/guia', [TaxCalculatorController::class, 'guide'])->name('guia');
});
