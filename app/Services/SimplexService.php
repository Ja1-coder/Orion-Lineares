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

                [$status1, $tableau, $iterHist1] = $this->runSimplexCore($tableau, $artificialIndexes);
                foreach ($iterHist1 as $t) $history[] = $t;

                if ($status1 === 'unbounded') {
                    return $this->formatResult('unbounded', [], null, $history, $varNames, array_merge($messages, ['Phase 1: unbounded']));
                }

                $phase1Value = $tableau[count($tableau) - 1][count($tableau[0]) - 1];
                if (abs($phase1Value) > self::EPS) {
                    return $this->formatResult('infeasible', [], null, $history, $varNames, array_merge($messages, ['Phase 1: problem infeasible (sum of artificials != 0)']));
                }

                [$tableau, $varNames] = $this->removeArtificialVariablesFromTableau($tableau, $varNames, $artificialIndexes);
                $history[] = $this->copyTableau($tableau);

                $c_extended = $this->extendObjectiveToCurrentVars($c_extended, $varNames);
                $tableau[count($tableau) - 1] = $this->computeObjectiveRowFromCosts($tableau, $c_extended);
                $history[] = $this->copyTableau($tableau);
            } else {
                $messages[] = 'Nenhuma variável artificial detectada: executando Simplex diretamente.';
            }

            [$status2, $tableau, $iterHist2] = $this->runSimplexCore($tableau);
            foreach ($iterHist2 as $t) $history[] = $t;

            if ($status2 === 'unbounded') {
                return $this->formatResult('unbounded', [], null, $history, $varNames, array_merge($messages, ['Phase 2: unbounded']));
            }

            $solution = $this->extractSolutionFromTableau($tableau, $varNames, $numVars);
            $zval = $tableau[count($tableau) - 1][count($tableau[0]) - 1];
            if ($isMin) $zval = -1.0 * $zval;

            $dualSolution = $this->extractDualSolution($tableau, $varNames, count($restricoes));
            $multipleInfo = $this->checkMultipleSolutions($tableau, $varNames, $solution);
            $messages[] = $multipleInfo['message'];

            // =========================
            // Filtra apenas interações reais (com pivot definido)
            // =========================
            $filteredHistory = array_filter($history, function($h) {
                return isset($h['pivot_row'], $h['pivot_col']) && $h['pivot_row'] !== null && $h['pivot_col'] !== null;
            });

            return $this->formatResult(
                'optimal',
                $solution,
                $zval,
                array_values($filteredHistory), // resetar chaves do array
                $varNames,
                array_merge($messages, [$multipleInfo['message']]),
                $dualSolution
            );

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

    private function toStandardForm(array $c_orig, array $restricoes): array
    {
        $numOrig = count($c_orig);
        $varNames = [];
        for ($i = 1; $i <= $numOrig; $i++) $varNames[] = "x{$i}";

        $rows = [];
        foreach ($restricoes as $r) {
            $coefs = array_map(fn($v) => floatval($v), $r['coefs']);
            $sinal = trim($r['sinal']);
            $rhs = floatval($r['rhs']);
            if ($rhs < 0) {
                $rhs *= -1;
                foreach ($coefs as $k => $v) $coefs[$k] = -1.0 * $v;
                if ($sinal === '<=') $sinal = '>=';
                elseif ($sinal === '>=') $sinal = '<=';
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
            while (count($line) < $numOrig) $line[] = 0.0;
            if ($currentExtra > 0) $line = array_merge($line, array_fill(0, $currentExtra, 0.0));
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

        $maxCols = max(array_map('count', $A));
        foreach ($A as &$r) while (count($r) < $maxCols) $r[] = 0.0;
        $c = array_map(fn($v) => floatval($v), $c_orig);
        while (count($c) < $maxCols) $c[] = 0.0;

        return [
            'A' => $A,
            'b' => $b,
            'c' => $c,
            'var_names' => $varNames,
            'artificial_indexes' => $artificialIndexes,
        ];
    }

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

    private function createPhase1ObjectiveRow(array $tableau, array $artificialIndexes): array
    {
        $numCols = count($tableau[0]);
        $phase1Obj = array_fill(0, $numCols, 0.0);
        foreach ($artificialIndexes as $col) {
            if ($col >= 0 && $col < $numCols) $phase1Obj[$col] = -1.0;
        }
        return $this->computePhase1ObjectiveRow($tableau, $phase1Obj, $artificialIndexes);
    }

    private function computePhase1ObjectiveRow(array $tableau, array $phase1Obj, array $artificialIndexes): array
    {
        $row = array_map(fn($v) => floatval($v), $phase1Obj);
        $numCols = count($tableau[0]);
        while (count($row) < $numCols) $row[] = 0.0;
        $m = count($tableau) - 1;

        foreach ($artificialIndexes as $colIndex) {
            $cB = $row[$colIndex] ?? 0.0;
            if (abs($cB) < self::EPS) continue;
            for ($i = 0; $i < $m; $i++) {
                $r = $tableau[$i];
                if (isset($r[$colIndex]) && abs($r[$colIndex] - 1.0) < self::EPS) {
                    for ($k = 0; $k < $numCols; $k++) $row[$k] += $cB * floatval($r[$k]);
                    break;
                }
            }
        }

        return $row;
    }

    private function runSimplexCore(array $tableau, array $artificialIndexes = []): array
    {
        $history = [];
        $numRows = count($tableau);
        $numCols = count($tableau[0]);
        $inPhase1 = !empty($artificialIndexes);

        $iter = 0;
        while (true) {
            $iter++;
            if ($iter > self::MAX_ITER) break;

            $lastRow = $tableau[$numRows - 1];
            $enterCol = null;
            $minVal = 0.0;
            for ($j = 0; $j < $numCols - 1; $j++) {
                if ($inPhase1 && in_array($j, $artificialIndexes, true)) continue;
                $v = $lastRow[$j];
                if ($v < $minVal - self::EPS) {
                    $minVal = $v;
                    $enterCol = $j;
                }
            }

            if ($enterCol === null) {
                $history[] = [
                    'tableau' => $this->copyTableau($tableau),
                    'pivot_row' => null,
                    'pivot_col' => null,
                ];
                return ['optimal', $tableau, $history];
            }

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
                    } elseif (abs($ratio - $bestRatio) < self::EPS && ($leaveRow === null || $i < $leaveRow)) {
                        $leaveRow = $i;
                    }
                }
            }

            if ($leaveRow === null) {
                $history[] = [
                    'tableau' => $this->copyTableau($tableau),
                    'pivot_row' => null,
                    'pivot_col' => null,
                ];
                return ['unbounded', $tableau, $history];
            }

            $tableau = $this->pivotOperation($tableau, $leaveRow, $enterCol);
            $history[] = [
                'tableau' => $this->copyTableau($tableau),
                'pivot_row' => $leaveRow,
                'pivot_col' => $enterCol,
            ];
        }

        return ['optimal', $tableau, $history];
    }

    private function pivotOperation(array $tableau, int $pivotRow, int $pivotCol): array
    {
        $numRows = count($tableau);
        $numCols = count($tableau[0]);
        $pivotVal = $tableau[$pivotRow][$pivotCol];
        if (abs($pivotVal) < self::EPS) throw new \Exception("Pivot value nearly zero.");

        for ($j = 0; $j < $numCols; $j++) $tableau[$pivotRow][$j] /= $pivotVal;

        for ($i = 0; $i < $numRows; $i++) {
            if ($i === $pivotRow) continue;
            $factor = $tableau[$i][$pivotCol];
            if (abs($factor) < self::EPS) continue;
            for ($j = 0; $j < $numCols; $j++) $tableau[$i][$j] -= $factor * $tableau[$pivotRow][$j];
        }

        return $tableau;
    }

    private function removeArtificialVariablesFromTableau(array $tableau, array $varNames, array $artificialIndexes): array
    {
        rsort($artificialIndexes);
        $numRows = count($tableau);
        $numCols = count($tableau[0]);

        foreach ($artificialIndexes as $artCol) {
            $basicRow = null;
            for ($i = 0; $i < $numRows - 1; $i++) {
                if (abs($tableau[$i][$artCol] - 1.0) < self::EPS) {
                    $isBasic = true;
                    for ($r = 0; $r < $numRows - 1; $r++) {
                        if ($r === $i) continue;
                        if (abs($tableau[$r][$artCol]) > self::EPS) { $isBasic = false; break; }
                    }
                    if ($isBasic) { $basicRow = $i; break; }
                }
            }

            if ($basicRow !== null) {
                $pivotFound = false;
                for ($j = 0; $j < $numCols - 1; $j++) {
                    if (in_array($j, $artificialIndexes, true)) continue;
                    if (abs($tableau[$basicRow][$j]) > self::EPS) {
                        $tableau = $this->pivotOperation($tableau, $basicRow, $j);
                        $pivotFound = true;
                        break;
                    }
                }
            }

            foreach ($tableau as &$row) array_splice($row, $artCol, 1);
            array_splice($varNames, $artCol, 1);
            $numCols = count($tableau[0]);
        }

        return [$tableau, $varNames];
    }

    private function extendObjectiveToCurrentVars(array $c_orig, array $varNames): array
    {
        $c_extended = [];
        $nOrig = count($c_orig);
        foreach ($varNames as $i => $name) {
            $c_extended[] = $i < $nOrig ? floatval($c_orig[$i]) : 0.0;
        }
        $c_extended[] = 0.0;
        return $c_extended;
    }

    private function computeObjectiveRowFromCosts(array $tableau, array $c_extended): array
    {
        $numCols = count($tableau[0]);
        $obj = array_map(fn($v) => -1.0 * floatval($v), $c_extended);
        $m = count($tableau) - 1;

        for ($i = 0; $i < $m; $i++) {
            $pivotCol = $this->findBasicColumnInRow($tableau, $i);
            if ($pivotCol !== null) {
                $cB = $c_extended[$pivotCol] ?? 0.0;
                for ($j = 0; $j < $numCols; $j++) $obj[$j] += $cB * $tableau[$i][$j];
            }
        }

        while (count($obj) < $numCols) $obj[] = 0.0;
        return $obj;
    }

    private function findBasicColumnInRow(array $tableau, int $rowIndex): ?int
    {
        $numCols = count($tableau[0]);
        $numRows = count($tableau) - 1;
        for ($col = 0; $col < $numCols - 1; $col++) {
            if (abs($tableau[$rowIndex][$col] - 1.0) > self::EPS) continue;
            $isBasic = true;
            for ($r = 0; $r < $numRows; $r++) {
                if ($r === $rowIndex) continue;
                if (abs($tableau[$r][$col]) > self::EPS) { $isBasic = false; break; }
            }
            if ($isBasic) return $col;
        }
        return null;
    }

    private function extractSolutionFromTableau(array $tableau, array $varNames, int $numVars): array
    {
        $solution = array_fill(0, $numVars, 0.0);
        $m = count($tableau) - 1;
        $n = count($tableau[0]) - 1;

        for ($col = 0; $col < min(count($varNames), $n); $col++) {
            $name = $varNames[$col];
            if (!str_starts_with($name, 'x')) continue;
            $oneCount = 0;
            $oneRow = null;
            $nonZeroOther = false;
            for ($i = 0; $i < $m; $i++) {
                if (abs($tableau[$i][$col] - 1.0) < self::EPS) { $oneCount++; $oneRow = $i; }
                elseif (abs($tableau[$i][$col]) > self::EPS) { $nonZeroOther = true; break; }
            }
            if ($oneCount === 1 && !$nonZeroOther && $oneRow !== null) {
                $idx = intval(substr($name, 1)) - 1;
                if ($idx >= 0 && $idx < $numVars) $solution[$idx] = $tableau[$oneRow][$n];
            }
        }

        return $solution;
    }

    private function copyTableau(array $tableau): array
    {
        $copy = [];
        foreach ($tableau as $r) $copy[] = array_map(fn($v) => floatval($v), $r);
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
            if ($slackIndex > $numRests) break;
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

    public function checkMultipleSolutions(array $tableau, array $varNames, array $solution): array
    {
        $numRows = count($tableau) - 1;
        $numCols = count($tableau[0]);
        $lastRow = $tableau[$numRows];

        $multiple = false;
        $freeVars = [];

        for ($j = 0; $j < $numCols - 1; $j++) {
            $name = $varNames[$j];
            $isBasic = false;

            // Verifica se é coluna básica
            for ($i = 0; $i < $numRows; $i++) {
                if (abs($tableau[$i][$j] - 1.0) < self::EPS) {
                    $sumOtherRows = 0.0;
                    for ($r = 0; $r < $numRows; $r++) {
                        if ($r !== $i) $sumOtherRows += abs($tableau[$r][$j]);
                    }
                    if ($sumOtherRows < self::EPS) $isBasic = true;
                }
            }

            // Se não é básica e custo reduzido é zero → variável livre
            if (!$isBasic && abs($lastRow[$j]) < self::EPS) {
                $multiple = true;
                $freeVars[] = $name;
            }
        }

        $message = $multiple
            ? 'Existem múltiplas soluções ótimas. Variáveis livres: ' . implode(', ', $freeVars)
            : 'Solução ótima única.';

        return [
            'multiple' => $multiple,
            'free_vars' => $freeVars,
            'message' => $message
        ];
    }

}
