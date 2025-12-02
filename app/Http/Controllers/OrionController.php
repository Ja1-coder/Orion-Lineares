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

        // Converter Z e Restrições
        $Z = array_map('floatval', $request->z);
        $restricoes = [];
        foreach ($request->restricoes as $r) {
            $restricoes[] = [
                'coefs' => array_map('floatval', $r['coefs']),
                'sinal' => $r['sinal'],
                'rhs'   => floatval($r['rhs']),
            ];
        }

        // Resolver Simplex
        $resultado = $this->simplex->resolver($tipo, $Z, $restricoes, $numVars);

        // Gerar Gráfico (Apenas 2 variáveis)
        $grafico = [];
        if ($numVars == 2) {
            $grafico = $this->gerarDadosGrafico($restricoes, $Z);
            
            // Adicionar ponto ótimo se existir
            if ($resultado['status'] == 'optimal') {
                $grafico['ponto_otimo'] = [
                    'x' => $resultado['solution'][0],
                    'y' => $resultado['solution'][1]
                ];
            }
        }

        return view('resultado', compact('resultado', 'grafico', 'numVars'));
    }

    private function gerarDadosGrafico($restricoes, $zCoefs)
    {
        // 1. Calcular o limite máximo dos eixos (Zoom Dinâmico)
        $maxIntercept = 0;
        foreach ($restricoes as $res) {
            $a = floatval($res['coefs'][0]);
            $b = floatval($res['coefs'][1]);
            $c = floatval($res['rhs']);
            
            // Verifica onde a linha corta os eixos X e Y
            if ($a > 0.0001) $maxIntercept = max($maxIntercept, $c / $a);
            if ($b > 0.0001) $maxIntercept = max($maxIntercept, $c / $b);
        }

        // Se não achou nada (tudo zero), define 10. Se achou, adiciona 10% de folga.
        $limit = ($maxIntercept > 0) ? $maxIntercept * 1.1 : 10;

        $linhas = [];

        foreach ($restricoes as $i => $res) {
            $a = floatval($res['coefs'][0]);
            $b = floatval($res['coefs'][1]);
            $c = floatval($res['rhs']);
            
            $pontos = [];

            // Pontos de interceptação (Eixos)
            if (abs($b) > 0.0001) $pontos[] = ['x' => 0, 'y' => $c / $b];
            if (abs($a) > 0.0001) $pontos[] = ['x' => $c / $a, 'y' => 0];

            // Tratamento para linhas horizontais/verticais que precisam esticar até o limite
            if (count($pontos) < 2) {
                if (abs($b) < 0.0001 && abs($a) > 0.0001) { 
                    // Vertical x = c/a. Desenha de y=0 até y=Limit
                    $pontos = [['x' => $c/$a, 'y' => 0], ['x' => $c/$a, 'y' => $limit]];
                } elseif (abs($a) < 0.0001 && abs($b) > 0.0001) { 
                    // Horizontal y = c/b. Desenha de x=0 até x=Limit
                    $pontos = [['x' => 0, 'y' => $c/$b], ['x' => $limit, 'y' => $c/$b]];
                }
            }

            // Ordenar pontos para o Chart.js desenhar a linha corretamente
            usort($pontos, function($p1, $p2) {
                return $p1['x'] <=> $p2['x'];
            });

            if (count($pontos) >= 2) {
                $linhas[] = [
                'label' => "R." . ($i + 1),
                    'coords' => $pontos
                ];
            }
        }

        return [
            'restricoes' => $linhas,
            'z_coefs' => $zCoefs,
            'max_limit' => $limit // Envia o limite calculado para o Front-end
        ];
    }
}