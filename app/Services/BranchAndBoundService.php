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

        $table = []; // tabela detalhada

        while (!empty($nodes)) {
            $node = array_pop($nodes);

            // Executa o simplex para o nó atual
            $r = $this->simplex->resolver(
                $tipo,
                $objective,
                $node['restricoes'],
                count($objective)
            );

            $row = [
                'node' => $node['desc'],
                'solution' => $r['solution'] ?? null,
                'Z' => $r['value'] ?? null,
                'status' => $r['status'] ?? 'unknown',
                'decision' => ''
            ];

            if ($r['status'] !== 'optimal') {
                $row['decision'] = 'Nó inviável / ilimitado – descartado';
                $table[] = $row;
                continue;
            }

            $z = $r['value'];
            $sol = $r['solution'];

            // Poda por bound
            $poda = false;
            if ($best['value'] !== null) {
                if ($tipo === 'max' && $z <= $best['value'] + 1e-9) {
                    $row['decision'] = 'Poda por bound (max)';
                    $poda = true;
                }
                if ($tipo === 'min' && $z >= $best['value'] - 1e-9) {
                    $row['decision'] = 'Poda por bound (min)';
                    $poda = true;
                }
            }

            if ($poda) {
                $table[] = $row;
                continue;
            }

            // Checar integralidade
            $fracIndex = $this->findFractional($sol, $integers);

            if ($fracIndex === null) {
                // Nova melhor solução
                $best['value'] = $z;
                $best['solution'] = $sol;
                $row['decision'] = "Melhor solução inteira encontrada";
                $table[] = $row;
                continue;
            }

            // Variável fracionária encontrada
            $xk = $sol[$fracIndex];
            $floor = floor($xk);
            $ceil = ceil($xk);

            $row['decision'] = "Variável x".($fracIndex+1)." = $xk fracionária → branch";
            $table[] = $row;

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

            $nodes[] = [
                'restricoes' => $left,
                'desc' => $node['desc'] . " -> x".($fracIndex+1)." <= $floor"
            ];
            $nodes[] = [
                'restricoes' => $right,
                'desc' => $node['desc'] . " -> x".($fracIndex+1)." >= $ceil"
            ];
        }

        if ($best['value'] === null) {
            return [
                'status' => 'infeasible',
                'history' => $table
            ];
        }

        return [
            'status' => 'optimal',
            'solution' => $best['solution'],
            'value' => $best['value'],
            'history' => $table
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
