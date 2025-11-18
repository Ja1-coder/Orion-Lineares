<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Services\SimplexService;


class OrionController extends Controller
{
    protected $simplex;

    public function __construct(SimplexService $simplex)
    {
        $this->simplex = $simplex;
    }

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
        //dd($request->all());
        $tipo = $request->tipo_problema;
        $numVars = $request->num_variaveis;
        $numRests = $request->num_restricoes;

        $Z = array_map('floatval', $request->z);

        $restricoes = [];
        foreach ($request->restricoes as $r) {
            $restricoes[] = [
                'coefs' => array_map('floatval', $r['coefs']),
                'sinal' => $r['sinal'],
                'rhs'   => floatval($r['rhs']),
            ];
        }

        // agora usamos o serviço
        $resultado = $this->simplex->resolver($tipo, $Z, $restricoes, $numVars);

        //dd($resultado);
        // retornar a view com o resultado
        return view('resultado', compact('resultado'));
    }

}
