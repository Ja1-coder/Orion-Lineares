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

        $resultado = $this->simplex->resolver($tipo, $Z, $restricoes, $numVars);

        $grafico = [];
        if ($numVars == 2) {
            $grafico = $this->gerarDadosGrafico($restricoes, $Z);
        }

        if ($numVars == 2 && $resultado['status'] == 'optimal') {
            $grafico['ponto_otimo'] = [
                'x' => $resultado['solution'][0], // x1
                'y' => $resultado['solution'][1]  // x2
            ];
        }

        return view('resultado', compact('resultado', 'grafico', 'numVars'));
    }

    private function gerarDadosGrafico($restricoes, $zCoefs)
    {
        $linhas = [];
        
        foreach ($restricoes as $i => $res) {
            $a = $res['coefs'][0];
            $b = $res['coefs'][1];
            $c = $res['rhs'];

            $pontos = [];
            
            if ($b != 0) {
                $pontos[] = ['x' => 0, 'y' => $c / $b];
            } else {
                $pontos[] = ['x' => $c/$a, 'y' => 0];
                $pontos[] = ['x' => $c/$a, 'y' => 100]; 
            }

            if ($a != 0 && $b != 0) {
                 $pontos[] = ['x' => $c / $a, 'y' => 0];
            }

            if (count($pontos) >= 2) {
                $linhas[] = [
                    'label' => "R." . ($i + 1),
                    'coords' => $pontos
                ];
            }
        }

        return [
            'restricoes' => $linhas,
            'z_inclinacao' => ($zCoefs[1] != 0) ? -($zCoefs[0]/$zCoefs[1]) : 0 
        ];
    }
}