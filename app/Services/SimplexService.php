<?php

namespace App\Services;

class SimplexService
{
    private const MAX_ITER = 1000;
    private const EPS = 1e-9;

    public function resolver(string $tipo, array $objective, array $restricoes, int $numVars): array
    {
        try {
            $messages = [];
            $isMin = $this->isMinimization($tipo);
            $c = array_map(fn($v) => floatval($v), $objective);
            if ($isMin) {
                $c = array_map(fn($v) => -1.0 * $v, $c);
                $messages[] = 'Minimização detectada: objetivo convertido (multiplicado por -1) para resolver pela forma de maximização.';
            }

            $std = $this->toStandardForm($c, $restricoes);
            $A = $std['A'];
            $b = $std['b'];
            $c_extended = $std['c'];
            $varNames = $std['var_names'];
            $artificialIndexes = $std['artificial_indexes'];

            $tableau = $this->buildTableau($A, $b, $c_extended);
            $history = [];
            $history[] = $this->copyTableau($tableau);

            if (!empty($artificialIndexes)) {
                $messages[] = 'Artificiais detectadas: executando Fase 1 (remover artificiais).';

                $tableau[count($tableau) - 1] = $this->createPhase1ObjectiveRow($tableau, $artificialIndexes);
                $history[] = $this->copyTableau($tableau);

                // run simplex core (maximização) for phase1, bloqueando artificiais como entrantes
                [$status1, $tableau, $iterHist1] = $this->runSimplexCore($tableau, $artificialIndexes);
                foreach ($iterHist1 as $t)
                    $history[] = $t;

                if ($status1 === 'unbounded') {
                    // more debug info in messages: attach tableau snapshot and var names
                    $messages[] = 'Phase 1: unbounded';
                    return $this->formatResult('unbounded', [], null, $history, $varNames, array_merge($messages, ['Phase 1: unbounded']));
                }

                // check phase1 optimal value (should be zero)
                $phase1Value = $tableau[count($tableau) - 1][count($tableau[0]) - 1];
                if (abs($phase1Value) > self::EPS) {
                    return $this->formatResult('infeasible', [], null, $history, $varNames, array_merge($messages, ['Phase 1: problem infeasible (sum of artificials != 0)']));
                }

                // remove artificial columns (with pivot-out se necessário)
                [$tableau, $varNames] = $this->removeArtificialVariablesFromTableau($tableau, $varNames, $artificialIndexes);
                $history[] = $this->copyTableau($tableau);

                // rebuild objective row from original costs
                $c_extended = $this->extendObjectiveToCurrentVars($c_extended, $varNames); // c_extended contains original c in initial order
                $tableau[count($tableau) - 1] = $this->computeObjectiveRowFromCosts($tableau, $c_extended);
                $history[] = $this->copyTableau($tableau);
            } else {
                $messages[] = 'Nenhuma variável artificial detectada: executando Simplex diretamente.';
            }

            // 5) Phase 2 (or single phase)
            [$status2, $tableau, $iterHist2] = $this->runSimplexCore($tableau);
            foreach ($iterHist2 as $t)
                $history[] = $t;

            if ($status2 === 'unbounded') {
                return $this->formatResult('unbounded', [], null, $history, $varNames, array_merge($messages, ['Phase 2: unbounded']));
            }

            // 6) Extract solution for original variables
            $solution = $this->extractSolutionFromTableau($tableau, $varNames, $numVars);

            $zval = $tableau[count($tableau) - 1][count($tableau[0]) - 1];
            if ($isMin)
                $zval = -1.0 * $zval;

            $dualSolution = $this->extractDualSolution($tableau, $varNames, count($restricoes));

            return $this->formatResult('optimal', $solution, $zval, $history, $varNames, $messages, $dualSolution);
        } catch (\Throwable $e) {
            return $this->formatResult('error', [], null, [], [], [$e->getMessage()]);
        }
    }

    /* --------------------- Helpers --------------------- */

    private function isMinimization(string $tipo): bool
    {
        $t = strtolower(trim($tipo));
        return in_array($t, ['min', 'minimizar', 'minimize']);
    }

    /**
     * Convert to standard form: build A, b, c_extended, var_names, artificial_indexes.
     */
    private function toStandardForm(array $c_orig, array $restricoes): array
    {
        $numOrig = count($c_orig);
        $varNames = [];
        for ($i = 1; $i <= $numOrig; $i++)
            $varNames[] = "x{$i}";

        $rows = [];
        foreach ($restricoes as $r) {
            $coefs = array_map(fn($v) => floatval($v), $r['coefs']);
            $sinal = trim($r['sinal']);
            $rhs = floatval($r['rhs']);

            // ensure b >= 0
            if ($rhs < 0) {
                $rhs *= -1;
                foreach ($coefs as $k => $v)
                    $coefs[$k] = -1.0 * $v;
                if ($sinal === '<=')
                    $sinal = '>=';
                elseif ($sinal === '>=')
                    $sinal = '<=';
            }

            $rows[] = ['coefs' => $coefs, 'sinal' => $sinal, 'rhs' => $rhs];
        }

        $A = [];
        $b = [];
        $artificialIndexes = [];
        $currentExtra = 0;
        $slackCount = 0;
        $artCount = 0;

        foreach ($rows as $i => $r) {
            $line = $r['coefs'];
            while (count($line) < $numOrig)
                $line[] = 0.0;
            if ($currentExtra > 0)
                $line = array_merge($line, array_fill(0, $currentExtra, 0.0));

            $sinal = $r['sinal'];
            $rhs = $r['rhs'];

            if ($sinal === '<=') {
                $slackCount++;
                $line[] = 1.0;
                $varNames[] = "s{$slackCount}";
                $currentExtra++;
            } elseif ($sinal === '>=') {
                $slackCount++;
                $line[] = -1.0;
                $varNames[] = "e{$slackCount}";
                $currentExtra++;

                $artCount++;
                $line[] = 1.0;
                $varNames[] = "a{$artCount}";
                $artificialIndexes[] = count($varNames) - 1;
                $currentExtra++;
            } elseif ($sinal === '=') {
                $artCount++;
                $line[] = 1.0;
                $varNames[] = "a{$artCount}";
                $artificialIndexes[] = count($varNames) - 1;
                $currentExtra++;
            } else {
                throw new \Exception("Unsupported sign: {$sinal}");
            }

            $A[] = $line;
            $b[] = $rhs;
        }

        // pad rows
        $maxCols = 0;
        foreach ($A as $r)
            $maxCols = max($maxCols, count($r));
        foreach ($A as &$r)
            while (count($r) < $maxCols)
                $r[] = 0.0;

        $c = array_map(fn($v) => floatval($v), $c_orig);
        while (count($c) < $maxCols)
            $c[] = 0.0;

        return [
            'A' => $A,
            'b' => $b,
            'c' => $c,
            'var_names' => $varNames,
            'artificial_indexes' => $artificialIndexes,
        ];
    }

    /**
     * Build tableau: each row ends with RHS. Last row is objective (-c for maximization).
     */
    private function buildTableau(array $A, array $b, array $c): array
    {
        $tableau = [];
        foreach ($A as $i => $row) {
            $r = array_map(fn($v) => floatval($v), $row);
            $r[] = floatval($b[$i]);
            $tableau[] = $r;
        }

        $obj = array_map(fn($v) => -1.0 * floatval($v), $c);
        $obj[] = 0.0;
        $tableau[] = $obj;

        return $tableau;
    }

    /**
     * Create initial phase1 objective row: zeros and -1 for artificial columns,
     * then adjust by adding cB * constraint rows (computePhase1ObjectiveRow will do this).
     */
    private function createPhase1ObjectiveRow(array $tableau, array $artificialIndexes): array
    {
        $numCols = count($tableau[0]);
        // start with zeros
        $phase1Obj = array_fill(0, $numCols, 0.0);

        // set -1 for artificial columns (RHS included as last col, left 0)
        foreach ($artificialIndexes as $col) {
            if ($col >= 0 && $col < $numCols)
                $phase1Obj[$col] = -1.0;
        }

        // let computePhase1ObjectiveRow incorporate constraint rows correctly
        return $this->computePhase1ObjectiveRow($tableau, $phase1Obj, $artificialIndexes);
    }

    /**
     * Compute initial phase1 objective row by adding rows where artificials appear.
     * phase1Obj contains -1 at artificial indexes (we maximize -sum(a)).
     *
     * CORRIGIDO: somente iterar linhas de restrição (excluindo a última linha)
     * e usar cB * row (onde cB vem de phase1Obj) ao somar — isto garante o sinal correto.
     */
    private function computePhase1ObjectiveRow(array $tableau, array $phase1Obj, array $artificialIndexes): array
    {
        $row = array_map(fn($v) => floatval($v), $phase1Obj);
        $numCols = count($tableau[0]);
        while (count($row) < $numCols)
            $row[] = 0.0;

        $m = count($tableau) - 1; // only constraint rows, exclude last row (objective)

        foreach ($artificialIndexes as $colIndex) {
            // cB is the cost of the artificial in the phase1 objective (should be -1)
            $cB = $row[$colIndex] ?? 0.0; // phase1Obj already placed -1 at artificial indices
            if (abs($cB) < self::EPS)
                continue;

            for ($i = 0; $i < $m; $i++) {
                $r = $tableau[$i];
                if (isset($r[$colIndex]) && abs($r[$colIndex] - 1.0) < self::EPS) {
                    // add cB * constraint row to the objective row
                    for ($k = 0; $k < $numCols; $k++) {
                        $row[$k] += $cB * floatval($r[$k]);
                    }
                    break;
                }
            }
        }

        return $row;
    }

    /**
     * Runs the simplex core (maximization). Returns [status, finalTableau, historyOfIterations]
     * status: 'optimal' or 'unbounded'
     *
     * $artificialIndexes (optional): when non-empty, we are in Phase 1 and must not allow
     * artificial columns to be chosen as entering variables.
     */
    private function runSimplexCore(array $tableau, array $artificialIndexes = []): array
    {
        $history = [];
        $numRows = count($tableau);
        $numCols = count($tableau[0]);

        $inPhase1 = !empty($artificialIndexes);

        $iter = 0;
        while (true) {
            $iter++;
            if ($iter > self::MAX_ITER) {
                // stop to avoid infinite loop
                break;
            }

            // ---------------------
            // Find entering column (most negative coefficient in objective row)
            // Skip artificial columns if in Phase 1
            // ---------------------
            $lastRow = $tableau[$numRows - 1];
            $enterCol = null;
            $minVal = 0.0;
            for ($j = 0; $j < $numCols - 1; $j++) {
                // if in phase1, skip artificial columns
                if ($inPhase1 && in_array($j, $artificialIndexes, true))
                    continue;

                $v = $lastRow[$j];
                if ($v < $minVal - self::EPS) {
                    $minVal = $v;
                    $enterCol = $j;
                }
            }

            // if no entering column => optimal reached
            if ($enterCol === null) {
                // optionally save final tableau (no pivot)
                $history[] = [
                    'tableau' => $this->copyTableau($tableau),
                    'pivot_row' => null,
                    'pivot_col' => null,
                ];

                return ['optimal', $tableau, $history];
            }

            // ---------------------
            // Find leaving row by minimum positive ratio b / a_{i,enter}
            // ---------------------
            $bestRatio = INF;
            $leaveRow = null;
            for ($i = 0; $i < $numRows - 1; $i++) {
                $a = $tableau[$i][$enterCol];
                $b = $tableau[$i][$numCols - 1];
                if ($a > self::EPS) {
                    $ratio = $b / $a;
                    if ($ratio < $bestRatio - self::EPS) {
                        $bestRatio = $ratio;
                        $leaveRow = $i;
                    } elseif (abs($ratio - $bestRatio) < self::EPS) {
                        // tie-break: choose smaller index (simple Bland-like)
                        if ($leaveRow === null || $i < $leaveRow)
                            $leaveRow = $i;
                    }
                }
            }

            if ($leaveRow === null) {
                // unbounded: save current tableau and return
                $history[] = [
                    'tableau' => $this->copyTableau($tableau),
                    'pivot_row' => null,
                    'pivot_col' => null,
                ];
                return ['unbounded', $tableau, $history];
            }

            // ---------------------
            // PERFORM PIVOT: normalize & eliminate
            // ---------------------
            $tableau = $this->pivotOperation($tableau, $leaveRow, $enterCol);

            // ---------------------
            // Save tableau AFTER pivot, including which pivot we used
            // ---------------------
            $history[] = [
                'tableau' => $this->copyTableau($tableau),
                'pivot_row' => $leaveRow,
                'pivot_col' => $enterCol,
            ];

            // loop continues to next iteration (will compute new entering column etc)
        }

        // fallback
        return ['optimal', $tableau, $history];
    }


    private function pivotOperation(array $tableau, int $pivotRow, int $pivotCol): array
    {
        $numRows = count($tableau);
        $numCols = count($tableau[0]);

        $pivotVal = $tableau[$pivotRow][$pivotCol];
        if (abs($pivotVal) < self::EPS) {
            throw new \Exception("Pivot value nearly zero.");
        }

        // normalize pivot row
        for ($j = 0; $j < $numCols; $j++) {
            $tableau[$pivotRow][$j] = $tableau[$pivotRow][$j] / $pivotVal;
        }

        // eliminate pivot column from other rows
        for ($i = 0; $i < $numRows; $i++) {
            if ($i === $pivotRow)
                continue;
            $factor = $tableau[$i][$pivotCol];
            if (abs($factor) < self::EPS)
                continue;
            for ($j = 0; $j < $numCols; $j++) {
                $tableau[$i][$j] = $tableau[$i][$j] - $factor * $tableau[$pivotRow][$j];
            }
        }

        return $tableau;
    }

    /**
     * Remove artificial columns from tableau and varNames.
     *
     * CORRIGIDO: antes de remover, se a artificial for básica em alguma linha,
     * tenta pivotear com uma coluna não-artificial nessa linha.
     */
    private function removeArtificialVariablesFromTableau(array $tableau, array $varNames, array $artificialIndexes): array
    {
        // iterate artificials from largest index to smallest so splicing keeps indices valid
        rsort($artificialIndexes);

        $numRows = count($tableau);
        $numCols = count($tableau[0]);

        foreach ($artificialIndexes as $artCol) {
            // find if this column is basic in some constraint row
            $basicRow = null;
            for ($i = 0; $i < $numRows - 1; $i++) {
                if (abs($tableau[$i][$artCol] - 1.0) < self::EPS) {
                    // ensure other rows have zero in this column
                    $isBasic = true;
                    for ($r = 0; $r < $numRows - 1; $r++) {
                        if ($r === $i)
                            continue;
                        if (abs($tableau[$r][$artCol]) > self::EPS) {
                            $isBasic = false;
                            break;
                        }
                    }
                    if ($isBasic) {
                        $basicRow = $i;
                        break;
                    }
                }
            }

            if ($basicRow !== null) {
                // try to find a non-artificial column in this row to pivot in
                $pivotFound = false;
                for ($j = 0; $j < $numCols - 1; $j++) {
                    if (in_array($j, $artificialIndexes, true))
                        continue; // skip artificiais
                    if (abs($tableau[$basicRow][$j]) > self::EPS) {
                        // pivot to replace artificial by column j
                        $tableau = $this->pivotOperation($tableau, $basicRow, $j);
                        $pivotFound = true;
                        break;
                    }
                }

                // if pivot not found, the row is likely all zeros except artificial:
                // we'll still remove the artificial column; the row remains (RHS should be zero)
            }

            // after ensuring the artificial is not basic pivot-wise, remove the column
            foreach ($tableau as $ri => &$row) {
                array_splice($row, $artCol, 1);
            }
            array_splice($varNames, $artCol, 1);

            // update numCols after splice
            $numCols = count($tableau[0]);
        }

        return [$tableau, $varNames];
    }

    /**
     * Extend original c to match current varNames (zeros for slack/excess/artificials).
     * c_orig should be the original c vector (extended to initial var count previously).
     */
    private function extendObjectiveToCurrentVars(array $c_orig, array $varNames): array
    {
        $c_extended = [];
        $nOrig = count($c_orig);
        foreach ($varNames as $i => $name) {
            if ($i < $nOrig)
                $c_extended[] = floatval($c_orig[$i]);
            else
                $c_extended[] = 0.0;
        }
        $c_extended[] = 0.0; // RHS
        return $c_extended;
    }

    /**
     * Compute objective row from costs and current tableau (obj = -c + sum(c_B * row_B) ).
     */
    private function computeObjectiveRowFromCosts(array $tableau, array $c_extended): array
    {
        $numCols = count($tableau[0]);
        $obj = array_map(fn($v) => -1.0 * floatval($v), $c_extended);
        $m = count($tableau) - 1;

        for ($i = 0; $i < $m; $i++) {
            $pivotCol = $this->findBasicColumnInRow($tableau, $i);
            if ($pivotCol !== null) {
                $cB = $c_extended[$pivotCol] ?? 0.0;
                for ($j = 0; $j < $numCols; $j++) {
                    $obj[$j] += $cB * $tableau[$i][$j];
                }
            }
        }

        while (count($obj) < $numCols)
            $obj[] = 0.0;
        return $obj;
    }

    /**
     * Find basic column for a given row (column where row[col]==1 and other rows 0).
     * Returns index or null.
     */
    private function findBasicColumnInRow(array $tableau, int $rowIndex): ?int
    {
        $numCols = count($tableau[0]);
        $numRows = count($tableau) - 1;
        for ($col = 0; $col < $numCols - 1; $col++) {
            if (abs($tableau[$rowIndex][$col] - 1.0) > self::EPS)
                continue;
            $isBasic = true;
            for ($r = 0; $r < $numRows; $r++) {
                if ($r === $rowIndex)
                    continue;
                if (abs($tableau[$r][$col]) > self::EPS) {
                    $isBasic = false;
                    break;
                }
            }
            if ($isBasic)
                return $col;
        }
        return null;
    }

    /**
     * Extract solution for original variables x1..x_numVars.
     */
    private function extractSolutionFromTableau(array $tableau, array $varNames, int $numVars): array
    {
        $solution = array_fill(0, $numVars, 0.0);
        $m = count($tableau) - 1;
        $n = count($tableau[0]) - 1;

        for ($col = 0; $col < min(count($varNames), $n); $col++) {
            $name = $varNames[$col];
            if (!str_starts_with($name, 'x'))
                continue;
            $oneCount = 0;
            $oneRow = null;
            $nonZeroOther = false;
            for ($i = 0; $i < $m; $i++) {
                if (abs($tableau[$i][$col] - 1.0) < self::EPS) {
                    $oneCount++;
                    $oneRow = $i;
                } elseif (abs($tableau[$i][$col]) > self::EPS) {
                    $nonZeroOther = true;
                    break;
                }
            }
            if ($oneCount === 1 && !$nonZeroOther && $oneRow !== null) {
                $idx = intval(substr($name, 1)) - 1;
                if ($idx >= 0 && $idx < $numVars) {
                    $solution[$idx] = $tableau[$oneRow][$n];
                }
            }
        }

        return $solution;
    }

    private function copyTableau(array $tableau): array
    {
        $copy = [];
        foreach ($tableau as $r)
            $copy[] = array_map(fn($v) => floatval($v), $r);
        return $copy;
    }

    private function extractDualSolution(array $tableau, array $varNames, int $numRests): array
    {
        $dualValues = [];
        $lastRow = $tableau[count($tableau) - 1];
        $numCols = count($tableau[0]);

        $slackIndex = 1;

        foreach ($varNames as $colIndex => $name) {
            if (str_starts_with($name, 's') || str_starts_with($name, 'e')) {
                $dualValues["y{$slackIndex}"] = $lastRow[$colIndex];
                $slackIndex++;
            }
            if ($slackIndex > $numRests)
                break;
        }
        return $dualValues;
    }

    private function formatResult(string $status, array $solution, $value, array $history, array $varNames, array $messages, array $dual = []): array
    {
        return [
            'status' => $status,
            'solution' => $solution,
            'value' => $value,
            'tableau_history' => $history,
            'var_names' => $varNames,
            'messages' => $messages,
            'dual_solution' => $dual
        ];
    }
}
