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
            <h2 class="mb-3" style="font-weight: 600;">Resultados da Otimização</h2>
        </div>

        <div class="col-12 col-md-11 col-lg-10">

            @if(!empty($mensagens))
                <div class="mb-4">
                    @foreach($mensagens as $msg)
                        @php
                            $tipo = $msg['type'] ?? 'info';
                            $texto = $msg['text'] ?? $msg;
                        @endphp
                        <div class="alert alert-{{ $tipo }} py-2">
                            <i class="fa-solid fa-{{ $tipo == 'success' ? 'check-circle' : ($tipo == 'warning' ? 'triangle-exclamation' : 'info-circle') }} me-1"></i>
                            {{ $texto }}
                        </div>
                    @endforeach
                </div>
            @endif

            @if(isset($numVars) && $numVars == 2 && !empty($grafico))
                <div class="card card-light p-3 p-md-4 mb-4">
                    <h4 class="solution-title"><i class="fa-solid fa-chart-line me-2"></i>Visualização Gráfica (2 Variáveis)</h4>
                    
                    <div style="position: relative; height: 400px; width: 100%;">
                        <canvas id="graficoSimplex"></canvas>
                    </div>

                    <div class="text-center mt-3">
                        <span class="badge bg-danger me-2">● Ponto Ótimo</span>
                        <span class="badge me-2" style="background-color: #28a745; color: white; border: 3px solid #28a745; opacity: 0.8;">-- Curva de Nível Ótima (Z)</span>
                        <span class="text-muted small d-block mt-1">* Linhas sólidas representam as restrições. A linha tracejada representa a direção de crescimento de Z.</span>
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-light p-3 mb-4 h-100">
                        <div class="solution-box h-100">
                            <h2 class="solution-title">Solução Primal (Ótima)</h2>
                            
                            @if($resultado['multiple_solutions'] ?? false)
                                <div class="alert alert-info mt-3 py-2">
                                    <i class="fa-solid fa-lightbulb me-1"></i> **Solução Múltipla Detectada.**
                                    <small class="d-block" style="font-size: 0.75rem; opacity: 0.9;">Existem infinitas soluções ao longo de uma aresta da região viável.</small>
                                </div>
                            @endif

                            <ul class="solution-list">
                                @foreach($resultado['solution'] as $i => $valor)
                                    <li>
                                        <span class="solution-var">{{ $resultado['var_names'][$i] }}:</span>
                                        <span class="solution-value">{{ number_format($valor, 4, ',', '.') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="solution-optimal">
                                Z = <span class="solution-optimal-value">{{ number_format($resultado['value'], 4, ',', '.') }}</span>
                            </div>
                            
                            @php
                                $isInteger = true;
                                foreach($resultado['solution'] as $val) {
                                    if(abs($val - round($val)) > 0.001) $isInteger = false;
                                }
                            @endphp
                            
                            @if($isInteger)
                                <div class="alert alert-success mt-3 py-2">
                                    <i class="fa-solid fa-check-circle me-1"></i> Solução Inteira Naturalmente Encontrada!
                                </div>
                            @else
                                <div class="alert alert-warning mt-3 py-2">
                                    <i class="fa-solid fa-info-circle me-1"></i> Solução não é inteira.
                                    <small class="d-block" style="font-size: 0.75rem; opacity: 0.8;">Para garantir inteiros, seria necessário aplicar métodos como Branch & Bound.</small>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="{{ route('orion.solucao_inteira') }}"
                                    class="btn btn-orion"
                                    style="font-weight:600; padding:10px 18px; display:inline-block;">
                                        Ver Solução Inteira (Branch & Bound)
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-light p-3 mb-4 h-100">
                        <div class="solution-box h-100" style="border-left-color: #ff9800;">
                            <h2 class="solution-title" style="color: #e65100;">Análise de Sensibilidade (Dual)</h2>
                            <p class="text-muted small mb-3">Preços Sombra (y) indicam o valor marginal de cada recurso.</p>
                            
                            @if(isset($resultado['dual_solution']) && !empty($resultado['dual_solution']))
                                <ul class="solution-list">
                                    @foreach($resultado['dual_solution'] as $key => $val)
                                        <li>
                                            <span class="solution-var" style="color: #e65100;">{{ strtoupper($key) }}:</span>
                                            <span class="solution-value">{{ number_format($val, 4, ',', '.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="alert alert-secondary py-2">Dual não disponível (Fase 1 ou erro).</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
                                
            @if(!empty($resultado['tableau_history']))
            <div class="card card-light p-3 p-md-4 mt-4">
                <h4 class="solution-title mb-4">Histórico de Iterações (Forma Tabular)</h4>
                
                @foreach($resultado['tableau_history'] as $k => $step)
                    @php
                        $tableau = $step['tableau'] ?? $step;
                        $pivotRow = $step['pivot_row'] ?? null;
                        $pivotCol = $step['pivot_col'] ?? null;
                        $numCols = isset($tableau[0]) ? count($tableau[0]) - 1 : 0;
                        
                        $displayVarNames = $resultado['var_names'];
                        if (count($displayVarNames) < $numCols) {
                            for ($i = count($displayVarNames); $i < $numCols; $i++) $displayVarNames[] = 'v' . ($i + 1);
                        }
                        
                        $basicVars = [];
                        foreach ($tableau as $rowIndex => $row) {
                            $found = false;
                            for ($col = 0; $col < $numCols; $col++) {
                                $isBasicCol = true;
                                if (abs($row[$col] - 1) < 1e-6) {
                                    for ($r=0; $r < count($tableau); $r++) {
                                        if ($r !== $rowIndex && abs($tableau[$r][$col]) > 1e-6) {
                                            $isBasicCol = false; break;
                                        }
                                    }
                                    if ($isBasicCol) {
                                        $basicVars[$rowIndex] = $displayVarNames[$col] ?? 'col'.$col; $found = true; break;
                                    }
                                }
                            }
                            if (!$found) $basicVars[$rowIndex] = ($rowIndex === count($tableau) - 1) ? 'Z' : '';
                        }
                    @endphp

                    <table class="table-simplex">
                        <thead>
                            <tr><th colspan="{{ 1 + $numCols + 1 }}" class="iteration-header">Iteração {{ $k }}</th></tr>
                            <tr>
                                <th class="basic-cell">Base</th>
                                @foreach ($displayVarNames as $idx => $name)
                                    @if($idx < $numCols) <th class="basic-cell">{{ $name }}</th> @endif
                                @endforeach
                                <th class="basic-cell">RHS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tableau as $i => $linha)
                                <tr>
                                    <td class="basic-col">{{ $basicVars[$i] }}</td>
                                    @for($j = 0; $j < $numCols; $j++)
                                        @php
                                            $val = $linha[$j] ?? 0;
                                            $isP = ($pivotRow!==null && $pivotCol!==null && $pivotRow==$i && $pivotCol==$j);
                                            $isPR = ($pivotRow!==null && $pivotRow==$i);
                                            $isPC = ($pivotCol!==null && $pivotCol==$j);
                                        @endphp
                                        <td class="{{ $isP ? 'pivot-cell' : '' }} {{ $isPR && !$isP ? 'pivot-row' : '' }} {{ $isPC && !$isP ? 'pivot-col' : '' }}">
                                            {{ number_format($val, 4, ',', '.') }}
                                        </td>
                                    @endfor
                                    <td>{{ number_format($linha[$numCols]??0, 4, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
            @endif

        </div>
    </div>

    <style>
        .table-simplex { border-collapse: separate; border-spacing: 0; width: 100%; margin-bottom: 30px; font-size: 14px; border-radius: 6px; overflow: hidden; }
        .table-simplex .iteration-header { background: var(--orion-cabecalho-tabela); color: white; padding: 6px; border: 1px solid var(--orion-linhas-tabela); text-align: center; font-size: 0.9rem; }
        .table-simplex .basic-cell { background: var(--orion-celulas-tabela); padding: 6px; border: 1px solid var(--orion-linhas-tabela); text-align: center; font-weight: 600; }
        .table-simplex td { padding: 4px 8px; border: 1px solid var(--orion-linhas-tabela); text-align: right; }
        .basic-col { font-weight: bold; background: var(--orion-celulas-tabela); text-align: center !important; }
        .pivot-row { background-color: rgba(255, 230, 150, 0.4); }
        .pivot-col { background-color: rgba(150, 200, 255, 0.3); }
        .pivot-cell { background-color: #ff4444 !important; color: white; font-weight: bold; }
        
        .solution-box { background: var(--orion-cinza-claro); border-left: 5px solid var(--orion-ciano); padding: 15px; border-radius: 8px; }
        .solution-title { font-weight: 700; margin-bottom: 15px; color: var(--orion-azul-escuro); font-size: 1.2rem; }
        .solution-list { list-style: none; padding: 0; margin: 0; }
        .solution-list li { padding: 5px 0; border-bottom: 1px dashed rgba(0,0,0,0.1); display: flex; justify-content: space-between; }
        .solution-optimal { font-size: 1.1rem; font-weight: 700; color: var(--orion-azul-escuro); margin-top: 15px; }
        .solution-optimal-value { color: var(--orion-ciano); }
    </style>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if(isset($numVars) && $numVars == 2 && !empty($grafico))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('graficoSimplex').getContext('2d');
                const dados = @json($grafico);
                const datasets = [];

                dados.restricoes.forEach((res, index) => {
                    const hue = (index * 137.5) % 360;
                    datasets.push({
                        label: res.label,
                        data: res.coords,
                        borderColor: `hsl(${hue}, 70%, 50%)`,
                        backgroundColor: `hsla(${hue}, 70%, 50%, 0.1)`,
                        borderWidth: 2,
                        showLine: true,
                        fill: false,
                        tension: 0,
                        pointRadius: 0
                    });
                });

                let pOtimo = null;
                if (dados.solucao_otima && dados.solucao_otima.length > 1) {
                    datasets.push({
                        label: 'Soluções Ótimas',
                        data: dados.solucao_otima,
                        backgroundColor: '#dc3545',
                        borderColor: '#dc3545',
                        borderWidth: 2,
                        showLine: true,
                        fill: false,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        tension: 0
                    });
                } else if (dados.ponto_otimo) {
                    const pOtimo = dados.ponto_otimo;
                    datasets.push({
                        label: 'Solução Ótima',
                        data: [pOtimo],
                        backgroundColor: '#dc3545',
                        borderColor: '#dc3545',
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        type: 'scatter'
                    });
                }


                if (pOtimo && dados.z_coefs) {
                    const c1 = dados.z_coefs[0];
                    const c2 = dados.z_coefs[1];
                    const Zmax = c1 * pOtimo.x + c2 * pOtimo.y;
                    const ptsZ = [];
                    const limit = Math.max(pOtimo.x, pOtimo.y) * 2 + 5;

                    if (Math.abs(c2) > 0.001) {
                        ptsZ.push({ x: 0, y: Zmax/c2 });
                        ptsZ.push({ x: limit, y: (Zmax - c1*limit)/c2 });
                    } else {
                        ptsZ.push({ x: pOtimo.x, y: 0 });
                        ptsZ.push({ x: pOtimo.x, y: limit });
                    }

                    datasets.push({
                        label: 'Função Objetivo (Z)',
                        data: ptsZ,
                        borderColor: '#28a745',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        showLine: true,
                        fill: false
                    });
                }

                new Chart(ctx, {
                    type: 'scatter',
                    data: { datasets: datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { type: 'linear', position: 'bottom', title: {display:true, text:'X1'}, min: 0 },
                            y: { type: 'linear', title: {display:true, text:'X2'}, min: 0 }
                        },
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            });
        </script>
    @endif
@endpush
