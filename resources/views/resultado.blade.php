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
            @if(isset($numVars) && $numVars == 2 && !empty($grafico))
                <div class="card card-light p-3 p-md-4 mb-4">
                    <h4 class="solution-title"><i class="fa-solid fa-chart-line me-2"></i>Visualização Gráfica</h4>
                    <div style="position: relative; height: 400px; width: 100%;">
                        <canvas id="graficoSimplex"></canvas>
                    </div>
                    <p class="text-muted mt-2 text-center small">
                        * As linhas representam as restrições. O ponto vermelho indica a Solução Ótima calculada.
                    </p>
                </div>
            @endif

            <div class="card card-light p-3 p-md-4">
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

                @if(!empty($resultado['tableau_history']))

                    @foreach($resultado['tableau_history'] as $k => $step)

                        @php
                            // tableau pode vir como $step ou $step['tableau']
                            $tableau = $step['tableau'] ?? $step;
                            $pivotRow = $step['pivot_row'] ?? null;
                            $pivotCol = $step['pivot_col'] ?? null;

                            // número de colunas efetivas (exclui RHS)
                            $numCols = isset($tableau[0]) ? count($tableau[0]) - 1 : 0;

                            // construir lista de nomes de colunas exibidos:
                            // usa os var_names passados e, se houver colunas extras, cria nomes genéricos (vN)
                            $displayVarNames = $resultado['var_names'];
                            if (count($displayVarNames) < $numCols) {
                                for ($i = count($displayVarNames); $i < $numCols; $i++) {
                                    $displayVarNames[] = 'v' . ($i + 1);
                                }
                            }

                            // detectar variável básica em cada linha (apenas nas colunas 0..numCols-1)
                            $basicVars = [];
                            foreach ($tableau as $rowIndex => $row) {
                                $found = false;

                                for ($col = 0; $col < $numCols; $col++) {
                                    // construir a coluna
                                    $column = array_column($tableau, $col);

                                    $countOnes = 0;
                                    $countZeros = 0;

                                    foreach ($column as $v) {
                                        if (abs($v - 1) < 1e-6) $countOnes++;
                                        if (abs($v) < 1e-6) $countZeros++;
                                    }

                                    if ($countOnes === 1 && $countZeros === count($column) - 1) {
                                        if (abs($tableau[$rowIndex][$col] - 1) < 1e-6) {
                                            $basicVars[$rowIndex] = $displayVarNames[$col] ?? 'col'.$col;
                                            $found = true;
                                            break;
                                        }
                                    }
                                }

                                if (!$found) {
                                    // se for a última linha (objetivo) marca Z
                                    if ($rowIndex === count($tableau) - 1) {
                                        $basicVars[$rowIndex] = 'Z';
                                    } else {
                                        $basicVars[$rowIndex] = ''; // nada encontrado
                                    }
                                }
                            }

                            // colspan correto: 1 (Basic) + numCols (vars) + 1 (RHS)
                            $colspan = 1 + $numCols + 1;
                        @endphp

                        <table class="table-simplex">
                            <thead>
                                <tr>
                                    <th colspan="{{ $colspan }}" class="iteration-header">
                                        Iteração {{ $k }}
                                    </th>
                                </tr>

                                <tr>
                                    <th class="basic-cell">Basic</th>

                                    {{-- cabeçalho dinâmico das variáveis (apenas numCols) --}}
                                    @foreach ($displayVarNames as $idx => $name)
                                        @if($idx < $numCols)
                                            <th class="basic-cell">{{ $name }}</th>
                                        @endif
                                    @endforeach

                                    <th class="basic-cell">RHS</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($tableau as $i => $linha)
                                    <tr>
                                        <td class="basic-col">{{ $basicVars[$i] }}</td>

                                        {{-- exibir apenas as numCols colunas de variáveis + RHS --}}
                                        @for($j = 0; $j < $numCols; $j++)
                                            @php
                                                $val = $linha[$j] ?? 0;
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
                                        @endfor

                                        {{-- RHS (última coluna) --}}
                                        @php $rhs = $linha[$numCols] ?? 0; @endphp
                                        <td class="">{{ number_format($rhs, 4, ',', '.') }}</td>
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

        .solution-box {
            background: var(--orion-cinza-claro);
            border-left: 6px solid var(--orion-ciano);
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 35px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .solution-title {
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--orion-azul-escuro);
        }

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
            border-bottom: 1px dashed rgba(0, 0, 0, 0.15);
        }

        .solution-var {
            font-weight: 600;
            color: var(--orion-azul-medio);
        }

        .solution-value {
            font-weight: 600;
            color: var(--orion-texto-escuro);
        }

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

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if(isset($numVars) && $numVars == 2 && !empty($grafico))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('graficoSimplex').getContext('2d');

                const dadosGrafico = @json($grafico);

                const datasets = [];

                dadosGrafico.restricoes.forEach((res, index) => {
                    const hue = (index * 137.508) % 360;
                    const color = `hsl(${hue}, 70%, 50%)`;

                    datasets.push({
                        label: res.label,
                        data: res.coords,
                        borderColor: color,
                        backgroundColor: color,
                        borderWidth: 2,
                        showLine: true,
                        fill: false,
                        tension: 0
                    });
                });

                if (dadosGrafico.ponto_otimo) {
                    datasets.push({
                        label: 'Solução Ótima',
                        data: [dadosGrafico.ponto_otimo],
                        backgroundColor: 'red',
                        borderColor: 'red',
                        pointRadius: 8,
                        pointHoverRadius: 10,
                        type: 'scatter'
                    });
                }

                new Chart(ctx, {
                    type: 'scatter',
                    data: { datasets: datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'linear',
                                position: 'bottom',
                                title: { display: true, text: 'Variável X1' },
                                min: 0
                            },
                            y: {
                                type: 'linear',
                                title: { display: true, text: 'Variável X2' },
                                min: 0
                            }
                        },
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return `${context.dataset.label}: (${context.raw.x.toFixed(2)}, ${context.raw.y.toFixed(2)})`;
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endif
@endpush
