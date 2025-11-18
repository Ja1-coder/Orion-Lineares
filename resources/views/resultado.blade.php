@extends('layout.app')

@section('header')
    <nav class="header-orion">
        <a href="{{ route('orion.index') }}" class="logo-link">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="20" cy="8" r="2.5" fill="white"></circle>
                <circle cx="12" cy="18" r="2.5" fill="white"></circle>
                <circle cx="10" cy="30" r="2.5" fill="white"></circle>
                <circle cx="22" cy="28" r="2.5" fill="white"></circle>
                <circle cx="28" cy="22" r="2.5" fill="white"></circle>
                <line x1="20" y1="8" x2="12" y2="18" stroke="white" stroke-width="2"></line>
                <line x1="12" y1="18" x2="10" y2="30" stroke="white" stroke-width="2"></line>
                <line x1="12" y1="18" x2="28" y2="22" stroke="white" stroke-width="2"></line>
                <line x1="28" y1="22" x2="22" y2="28" stroke="white" stroke-width="2"></line>
            </svg>
            <div class="logo-link-text">
                <span>ORION</span>
                <span>LINEARIS</span>
            </div>
        </a>

        <a href="{{ route('orion.index') }}" class="btn-back">
            <i class="fa-solid fa-house"></i> Voltar ao Início 
        </a>
    </nav>
@endsection

@section('content')

<div class="row justify-content-center py-4 py-md-5">

    <div class="col-12 text-center mb-4">
        <h2 class="mb-3" style="font-weight: 600;">Iterações do Método Simplex</h2>
    </div>

    <div class="col-12 col-md-11 col-lg-10">
        <div class="card card-light p-3 p-md-4">
            {{-- ======================
                SOLUÇÃO FINAL
            ====================== --}}
            <div class="solution-box">
                <h2 class="solution-title">Solução Final</h2>

                <ul class="solution-list">
                    @foreach($resultado['solution'] as $i => $valor)
                        <li>
                            <span class="solution-var">{{ $resultado['var_names'][$i] }}:</span>
                            <span class="solution-value">{{ number_format($valor, 4, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="solution-optimal">
                    Valor ótimo:
                    <span class="solution-optimal-value">
                        {{ number_format($resultado['value'], 4, ',', '.') }}
                    </span>
                </div>
            </div>


            {{-- ======================
                ITERAÇÕES COM TABELAS
            ====================== --}}
            @if(!empty($resultado['tableau_history']))

                @foreach($resultado['tableau_history'] as $k => $step)

                    @php
                        // Detecta se a entrada é uma matriz pura ou um array estruturado
                        $tableau = $step['tableau'] ?? $step;
                        $pivotRow = $step['pivot_row'] ?? null;
                        $pivotCol = $step['pivot_col'] ?? null;

                        // Detectar variável básica usando colunas identidade
                        $basicVars = [];

                        foreach ($tableau as $rowIndex => $row) {

                            $found = false;

                            for ($col = 0; $col < count($resultado['var_names']); $col++) {
                                $column = array_column($tableau, $col);

                                // coluna identidade?
                                $countOnes = 0;
                                $countZeros = 0;

                                foreach ($column as $v) {
                                    if (abs($v - 1) < 1e-6) $countOnes++;
                                    if (abs($v) < 1e-6) $countZeros++;
                                }

                                if ($countOnes === 1 && $countZeros === count($column) - 1) {
                                    if (abs($tableau[$rowIndex][$col] - 1) < 1e-6) {
                                        $basicVars[$rowIndex] = $resultado['var_names'][$col];
                                        $found = true;
                                        break;
                                    }
                                }
                            }

                            if (!$found) {
                                $basicVars[$rowIndex] = 'Z';
                            }
                        }
                    @endphp
                    <table class="table-simplex">
                        {{-- LINHA DE TÍTULO DA ITERAÇÃO (COLSPAN COMPLETO) --}}
                        <thead>
                            <tr>
                                <th colspan="{{ 2 + count($resultado['var_names']) }}" class="iteration-header">
                                    Iteração {{ $k }}
                                </th>
                            </tr>

                            {{-- CABEÇALHO NORMAL --}}
                            <tr>
                                <th class="basic-cell">Basic</th>
                                @foreach ($resultado['var_names'] as $name)
                                    <th class="basic-cell">{{ $name }}</th>
                                @endforeach
                                <th class="basic-cell">RHS</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($tableau as $i => $linha)
                                <tr>
                                    <td class="basic-col">{{ $basicVars[$i] }}</td>
                                    @foreach($linha as $j => $val)
                                        @php
                                            $isPivot = $pivotRow !== null 
                                                    && $pivotCol !== null
                                                    && $pivotRow == $i 
                                                    && $pivotCol == $j;

                                            $isPivotRow = $pivotRow !== null && $pivotRow == $i;
                                            $isPivotCol = $pivotCol !== null && $pivotCol == $j;
                                        @endphp

                                        <td class="
                                            {{ $isPivot ? 'pivot-cell' : '' }}
                                            {{ $isPivotRow && !$isPivot ? 'pivot-row' : '' }}
                                            {{ $isPivotCol && !$isPivot ? 'pivot-col' : '' }}
                                        ">
                                            {{ number_format($val, 4, ',', '.') }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @endif
        </div>
    </div>
</div>

<style>
    .table-simplex {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        margin-bottom: 30px;
        font-size: 15px;
        border-radius: 6px;
        overflow: hidden;
    }

    .table-simplex .iteration-header {
        background: var(--orion-cabecalho-tabela);
        color: white;
        padding: 8px;
        border: 0.1rem solid var(--orion-linhas-tabela);
        text-align: center;
    }

    .table-simplex .basic-cell {
        background: var(--orion-celulas-tabela);
        color: black;
        padding: 8px;
        border: 0.1rem solid var(--orion-linhas-tabela);
        text-align: center;
    }

    .table-simplex td {
        padding: 6px 10px;
        border: 0.1rem solid var(--orion-linhas-tabela);
        text-align: right;
    }

    .basic-col {
        font-weight: bold;
        background: var(--orion-celulas-tabela);
        text-align: center !important;
    }

    .pivot-row {
        background-color: rgba(255, 230, 150, 0.6);
    }

    .pivot-col {
        background-color: rgba(150, 200, 255, 0.4);
    }

    .pivot-cell {
        background-color: #ff4444 !important;
        color: white;
        font-weight: bold;
    }

    /* Caixa da solução final */
    .solution-box {
        background: var(--orion-cinza-claro);
        border-left: 6px solid var(--orion-ciano);
        padding: 20px 25px;
        border-radius: 8px;
        margin-bottom: 35px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }

    /* Título */
    .solution-title {
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--orion-azul-escuro);
    }

    /* Lista das variáveis */
    .solution-list {
        list-style: none;
        padding: 0;
        margin: 0 0 18px 0;
    }

    .solution-list li {
        padding: 6px 0;
        font-size: 16px;
        display: flex;
        justify-content: space-between;
        border-bottom: 1px dashed rgba(0,0,0,0.15);
    }

    .solution-var {
        font-weight: 600;
        color: var(--orion-azul-medio);
    }

    .solution-value {
        font-weight: 600;
        color: var(--orion-texto-escuro);
    }

    /* Valor ótimo */
    .solution-optimal {
        font-size: 18px;
        font-weight: 600;
        color: var(--orion-azul-escuro);
    }

    .solution-optimal-value {
        color: var(--orion-ciano);
        font-weight: 700;
    }

</style>

@endsection
