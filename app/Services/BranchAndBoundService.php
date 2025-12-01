<?php

namespace App\Services;

class BranchAndBoundService
{
    private SimplexService $simplex;

    public function __construct()
    {
        $this->simplex = new SimplexService();
    }

    /**
     * $tipo → "max" ou "min"
     * $objective → array de coeficientes
     * $restricoes → [
     *      ['coefs'=>[], 'sinal'=>'<=', 'rhs'=>10],
     * ]
     * $integers → array com índices das variáveis que devem ser inteiras (0-based)
     */
    public function branchAndBound(string $tipo, array $objective, array $restricoes, array $integers)
    {
        $best = [
            'value' => null,
            'solution' => null
        ];

        $nodes = [];
        $history = [];

        // Nó raiz
        $nodes[] = [
            'restricoes' => $restricoes,
            'desc' => 'root'
        ];

        while (!empty($nodes)) {
            $node = array_pop($nodes);

            $history[] = "Processando nó: " . $node['desc'];

            // Executa o simplex para o nó atual
            $r = $this->simplex->resolver(
                $tipo,
                $objective,
                $node['restricoes'],
                count($objective)
            );

            if ($r['status'] !== 'optimal') {
                $history[] = "Nó inviável / ilimitado – descartado.";
                continue;
            }

            $z = $r['value'];
            $sol = $r['solution'];

            // Poda por bound
            if ($best['value'] !== null) {
                if ($tipo === 'max' && $z <= $best['value'] + 1e-9) {
                    $history[] = "Poda por bound (max).";
                    continue;
                }
                if ($tipo === 'min' && $z >= $best['value'] - 1e-9) {
                    $history[] = "Poda por bound (min).";
                    continue;
                }
            }

            // Checar integralidade
            $fracIndex = $this->findFractional($sol, $integers);

            if ($fracIndex === null) {
                // Nova melhor solução
                $best['value'] = $z;
                $best['solution'] = $sol;
                $history[] = "Melhor solução inteira encontrada: Z = $z";
                continue;
            }

            // Variável fracionária encontrada
            $xk = $sol[$fracIndex];
            $floor = floor($xk);
            $ceil = ceil($xk);

            $history[] = "Variável x".($fracIndex+1)." = $xk fracionária → branch criando dois nós.";

            // Nó esquerdo: xk <= floor
            $left = $node['restricoes'];
            $left[] = [
                'coefs' => $this->unitConstraint($fracIndex, count($objective)),
                'sinal' => '<=',
                'rhs' => $floor
            ];

            // Nó direito: xk >= ceil
            $right = $node['restricoes'];
            $right[] = [
                'coefs' => $this->unitConstraint($fracIndex, count($objective)),
                'sinal' => '>=',
                'rhs' => $ceil
            ];

            // Empilhar nós (DFS – mais eficiente)
            $nodes[] = [
                'restricoes' => $left,
                'desc' => "x".($fracIndex+1)." <= $floor"
            ];
            $nodes[] = [
                'restricoes' => $right,
                'desc' => "x".($fracIndex+1)." >= $ceil"
            ];
        }

        if ($best['value'] === null) {
            return [
                'status' => 'infeasible',
                'history' => $history
            ];
        }

        return [
            'status' => 'optimal',
            'solution' => $best['solution'],
            'value' => $best['value'],
            'history' => $history
        ];
    }


    /** Retorna índice da variável fracionária ou null */
    private function findFractional(array $sol, array $ints): ?int
    {
        foreach ($ints as $i) {
            if (abs($sol[$i] - round($sol[$i])) > 1e-9) {
                return $i;
            }
        }
        return null;
    }

    /** Cria vetor [0,0,1,0,0] para restrição x_k <= valor */
    private function unitConstraint(int $index, int $n)
    {
        $v = array_fill(0, $n, 0.0);
        $v[$index] = 1.0;
        return $v;
    }
}
