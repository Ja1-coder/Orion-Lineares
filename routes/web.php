<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrionController;

Route::get('/', [OrionController::class, 'index'])->name('orion.index');

Route::get('/definir', [OrionController::class, 'showDefinition'])->name('orion.definition');

Route::post('/gerar-tabela', [OrionController::class, 'generateTable'])->name('orion.generate.table');

Route::get('/entrada/{vars}/{rests}', [OrionController::class, 'showInputTable'])->name('orion.input.table');

Route::post('/resolver', [OrionController::class, 'solve'])->name('orion.solve');
