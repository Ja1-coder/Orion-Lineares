<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class OrionController extends Controller
{
    public function index(): View
    {
        return view('tela1');
    }

    public function showDefinition(): View
    {
        return view('tela2');
    }

    public function generateTable(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'num_variaveis' => 'required|integer|min:1',
            'num_restricoes' => 'required|integer|min:1',
            'metodo' => 'required|string',
        ]);

        return redirect()->route('orion.input.table', [
            'vars' => $data['num_variaveis'],
            'rests' => $data['num_restricoes'],
        ]);
    }

    public function showInputTable(int $vars, int $rests): View
    {
        return view('tela3', [
            'vars' => $vars,
            'rests' => $rests,
        ]);
    }

    public function solve(Request $request)
    {
        $dadosDoProblema = $request->except('_token');
        dd($dadosDoProblema);
    }
}
