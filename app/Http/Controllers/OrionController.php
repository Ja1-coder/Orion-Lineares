<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Services\SimplexService;
use App\Services\BranchAndBoundService;


class OrionController extends Controller
{
    protected $simplex;
    protected $bb;

    
    public function __construct(SimplexService $simplex, BranchAndBoundService $bb)
    {
        $this->simplex = $simplex;
        $this->bb = $bb;
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

        // --- 1. Preparar dados para o Simplex ---
        $Z = array_map('floatval', $request->z);

        $restricoes = [];
        foreach ($request->restricoes as $r) {
            $restricoes[] = [
                'coefs' => array_map('floatval', $r['coefs']),
                'sinal' => $r['sinal'],
                'rhs'   => floatval($r['rhs']),
            ];
        }

        session([
            'orion_input' => [
                'tipo' => $tipo,
                'numVars' => $numVars,
                'numRests' => $numRests,
                'Z' => $Z,
                'restricoes' => $restricoes
            ]
        ]);

        // Resolver Simplex
        $resultado = $this->simplex->resolver($tipo, $Z, $restricoes, $numVars);

        // --- 2. Gráfico (somente se 2 variáveis) ---
        $grafico = [];
        
        if ($numVars == 2) {

            // Restrições originais para o gráfico
            $originalRestricoes = [];
            foreach ($request->restricoes as $r) {
                $originalRestricoes[] = [
                    'coefs' => array_map('floatval', $r['coefs']),
                    'sinal' => $r['sinal'],
                    'rhs'   => floatval($r['rhs']),
                ];
            }

            // Função que gera as restrições para o gráfico
            $grafico = $this->gerarDadosGrafico($originalRestricoes, $Z);

            // Ponto ótimo
            if ($resultado['status'] === 'optimal') {
                $grafico['ponto_otimo'] = [
                    'x' => $resultado['solution'][0],
                    'y' => $resultado['solution'][1],
                ];
                $grafico['Z_otimo'] = $resultado['value'];
            }

            // Informações extras necessárias no JS
            $grafico['tipo_problema'] = $tipo;
            $grafico['z_coefs'] = $Z;  // <<< ESSENCIAL PARA CURVA DE NÍVEL
        }

        return view('resultado', compact('resultado', 'grafico', 'numVars'));
    }


    /**
     * Gera os dados de restrições (linhas) e o limite dos eixos para o gráfico.
     */
    private function gerarDadosGrafico(array $restricoes, array $zCoefs): array
    {
        $EPS = 1e-6;
        $maxIntercept = 0;

        // Encontrar o limite dos eixos (Zoom Dinâmico)
        foreach ($restricoes as $res) {
            $a = floatval($res['coefs'][0] ?? 0);
            $b = floatval($res['coefs'][1] ?? 0);
            $c = floatval($res['rhs']);
            
            // Só consideramos interceptos positivos ou zero para definir o limite
            if (abs($a) > $EPS && $c > -$EPS) $maxIntercept = max($maxIntercept, $c / $a);
            if (abs($b) > $EPS && $c > -$EPS) $maxIntercept = max($maxIntercept, $c / $b);
        }
        
        // Define um limite seguro para o eixo (1.5x o maior intercepto positivo, mínimo de 10)
        $limit = ($maxIntercept > 0) ? ceil($maxIntercept) * 1.5 : 10;
        $limit = max($limit, 10.0); // Garante um mínimo de 10

        $linhas = [];

        foreach ($restricoes as $i => $res) {
            $a = floatval($res['coefs'][0] ?? 0);
            $b = floatval($res['coefs'][1] ?? 0);
            $c = floatval($res['rhs']);
            
            $pontos = [];
            
            // Pontos de interceptação com os eixos
            if (abs($b) > $EPS) $pontos[] = ['x' => 0, 'y' => $c / $b];
            if (abs($a) > $EPS) $pontos[] = ['x' => $c / $a, 'y' => 0];

            // Pontos no limite máximo para garantir que a linha cubra a área de interesse
            // Ponto no x=limit
            if (abs($b) > $EPS) {
                $yAtLimit = ($c - $a * $limit) / $b;
                $pontos[] = ['x' => $limit, 'y' => $yAtLimit];
            }
            // Ponto no y=limit
            if (abs($a) > $EPS) {
                $xAtLimit = ($c - $b * $limit) / $a;
                $pontos[] = ['x' => $xAtLimit, 'y' => $limit];
            }
            
            // Limpar e ordenar pontos relevantes (apenas no quadrante X >= 0, Y >= 0)
            $pontosFiltados = [];
            foreach ($pontos as $p) {
                if (($p['x'] >= -$EPS) && ($p['y'] >= -$EPS)) {
                    // Limita os valores para evitar linhas muito longas no Chart.js
                    $p['x'] = max(0, $p['x']);
                    $p['y'] = max(0, $p['y']);
                    $pontosFiltados[] = $p;
                }
            }
            
            // Remove duplicatas
            $pontosFiltados = array_unique($pontosFiltados, SORT_REGULAR);

            // Ordenar pontos por x e depois por y
            usort($pontosFiltados, function($p1, $p2) {
                if ($p1['x'] != $p2['x']) return $p1['x'] <=> $p2['x'];
                return $p1['y'] <=> $p2['y'];
            });

            if (count($pontosFiltados) >= 2) {
                $linhas[] = [
                    'label' => "R." . ($i + 1),
                    'coefs' => [$a, $b],
                    'rhs' => $c,
                    'coords' => $pontosFiltados,
                    'sinal' => $res['sinal'] // Incluir o sinal da restrição (<=, >=, =)
                ];
            }
        }
        
        // Adicionar as restrições de não-negatividade (X >= 0, Y >= 0) como linhas implícitas
        $linhas[] = [
            'label' => 'X1 >= 0', 
            'coefs' => [1, 0], 
            'rhs' => 0,
            'coords' => [['x' => 0, 'y' => 0], ['x' => 0, 'y' => $limit]],
            'sinal' => '>='
        ];
        $linhas[] = [
            'label' => 'X2 >= 0', 
            'coefs' => [0, 1], 
            'rhs' => 0,
            'coords' => [['x' => 0, 'y' => 0], ['x' => $limit, 'y' => 0]],
            'sinal' => '>='
        ];


        return [
            'restricoes' => $linhas,
            'z_coefs' => $zCoefs, // [c1, c2] da função Z
            'max_limit' => $limit // Envia o limite calculado para o Front-end
        ];
    }

    public function solucaoInteira()
    {
        // Recupera os dados que já foram enviados
        $dados = session('orion_input');

        if (!$dados) {
            return redirect()->route('orion.index')
                ->with('error', 'Nenhum problema foi enviado ainda.');
        }

        // Extrai os parâmetros
        $tipo = $dados['tipo'];
        $Z = $dados['Z'];
        $restricoes = $dados['restricoes'];
        $numVars = (int) $dados['numVars'];

        // Gera nomes das variáveis: x1, x2, x3...
        $nomesVars = [];
        for ($i = 1; $i <= $numVars; $i++) {
            $nomesVars[] = "x{$i}";
        }

        // Reexecuta o simplex corretamente
        $resultado = $this->simplex->resolver($tipo, $Z, $restricoes, $numVars);

        // Agora aplica Branch & Bound corretamente
        $inteiras = range(0, $numVars - 1);

        $solucaoInteira = $this->bb->branchAndBound(
            $tipo,
            $Z,
            $restricoes,
            $inteiras
        );

        // ===============================
        //   Converter índices -> variáveis
        // ===============================
        if ($solucaoInteira['status'] === 'ok' && isset($solucaoInteira['solution'])) {

            $solucaoOriginal = $solucaoInteira['solution'];
            $solucaoFinal = [];

            foreach ($nomesVars as $i => $nomeVar) {
                $solucaoFinal[$nomeVar] = $solucaoOriginal[$i] ?? 0;
            }

            $solucaoInteira['solution'] = $solucaoFinal;
        }

        // ===============================
        //   Gráfico (se 2 variáveis)
        // ===============================
        $grafico = [];
        if ($numVars == 2) {
            // Usar as restrições originais para plotar
            $grafico = $this->gerarDadosGrafico($restricoes, $Z);

            // Adicionar ponto ótimo ao gráfico
            if ($solucaoInteira['status'] === 'ok') {

                // Recuperar valores corretamente usando x1, x2
                $grafico['ponto_otimo'] = [
                    'x' => $solucaoInteira['solution']['x1'] ?? 0,
                    'y' => $solucaoInteira['solution']['x2'] ?? 0
                ];

                $grafico['Z_otimo'] = $solucaoInteira['value'];
            }
        }

        return view('solucao_inteira', [
            'resultado' => $resultado,
            'solucaoInteira' => $solucaoInteira,
            'grafico' => $grafico,
        ]);
    }




}