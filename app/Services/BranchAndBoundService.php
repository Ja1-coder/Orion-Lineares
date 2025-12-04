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

        $nodes[] = [
            'restricoes' => $restricoes,
            'desc' => 'root'
        ];

        $table = [];

        while (!empty($nodes)) {
            $node = array_pop($nodes);

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

            $fracIndex = $this->findFractional($sol, $integers);

            if ($fracIndex === null) {
                $best['value'] = $z;
                $best['solution'] = $sol;
                $row['decision'] = "Melhor solução inteira encontrada";
                $table[] = $row;
                continue;
            }

            $xk = $sol[$fracIndex];
            $floor = floor($xk);
            $ceil = ceil($xk);

            $row['decision'] = "Variável x".($fracIndex+1)." = $xk fracionária → branch";
            $table[] = $row;

            $left = $node['restricoes'];
            $left[] = [
                'coefs' => $this->unitConstraint($fracIndex, count($objective)),
                'sinal' => '<=',
                'rhs' => $floor
            ];

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



    private function findFractional(array $sol, array $ints): ?int
    {
        foreach ($ints as $i) {
            if (abs($sol[$i] - round($sol[$i])) > 1e-9) {
                return $i;
            }
        }
        return null;
    }

    private function unitConstraint(int $index, int $n)
    {
        $v = array_fill(0, $n, 0.0);
        $v[$index] = 1.0;
        return $v;
    }
}
