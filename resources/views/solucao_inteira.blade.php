@extends('layout.app')

@section('header')
    <nav class="header-orion">
        <a href="{{ route('orion.index') }}" class="logo-link">
            <svg width="40" height="40" viewBox="0 0 40 40">
                <circle cx="20" cy="8" r="2.5" fill="white"></circle>
                <circle cx="12" cy="18" r="2.5" fill="white"></circle>
                <circle cx="10" cy="30" r="2.5" fill="white"></circle>
                <circle cx="22" cy="28" r="2.5" fill="white"></circle>
                <circle cx="28" cy="22" r="2.5" fill="white"></circle>
            </svg>
            <div class="logo-link-text">
                <span>ORION</span>
                <span>LINEARIS</span>
            </div>
        </a>

        <a href="{{ route('orion.definition') }}" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Voltar para Definição
        </a>
    </nav>
@endsection

@section('content')

<div class="row justify-content-center py-5">
    <div class="col-12 col-md-10">

        <h2 class="mb-4 text-center" style="font-weight:700;">
            Solução Inteira (Branch & Bound)
        </h2>

        <div class="card card-light p-4">

            {{-- CASO NÃO IMPLEMENTADO --}}
            @if($solucaoInteira['status'] === 'nao_implementado')
                <div class="alert alert-warning text-center">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    O módulo de Branch & Bound ainda não foi totalmente implementado.
                </div>

            @else
                <h4>Solução Inteira Encontrada:</h4>

                <ul class="solution-list">
                    @foreach($solucaoInteira['solution'] as $var => $val)
                        <li>
                            <span class="solution-var">X{{ $loop->iteration }}:</span>
                            <span class="solution-value">{{ $val }}</span>
                        </li>
                    @endforeach
                </ul>

                <h4 class="mt-3">Z = {{ $solucaoInteira['value'] }}</h4>
            @endif

        </div>

        {{-- ===================== --}}
        {{--  GRÁFICO (SE 2 VARS)  --}}
        {{-- ===================== --}}
        @if(isset($grafico) && isset($grafico['restricoes']) && count($grafico['restricoes']) > 0)
            <div class="card card-light p-4 mt-4">
                <h4 class="text-center mb-3">Gráfico da Solução Inteira</h4>

                {{-- CONTAINER COM ALTURA FIXA (IMPEDINDO CRESCIMENTO INFINITO) --}}
                <div style="position: relative; height: 400px; width: 100%;">
                    <canvas id="graficoSolucaoInteira"></canvas>
                </div>
            </div>
        @endif

    </div>
</div>

<style>
.solution-list {
    list-style:none;
    padding:0;
    margin-top:15px;
}
.solution-list li {
    padding:6px 0;
    display:flex;
    justify-content:space-between;
    border-bottom:1px dashed rgba(0,0,0,0.1);
}
.solution-var { font-weight:600; }
</style>

@endsection


{{-- ===================== --}}
{{--   SCRIPT DO GRÁFICO   --}}
{{-- ===================== --}}
@push('scripts')
@if(isset($grafico))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const canvas = document.getElementById('graficoSolucaoInteira');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    const restricoes = @json($grafico['restricoes']);
    const pontoOtimo = @json($solucaoInteira['solution'] ?? null);
    const limit = @json($grafico['max_limit']);

    const datasets = [];

    // Paleta fixa (igual ao gráfico normal)
    const cores = [
        "#007bff", "#28a745", "#ffc107", "#17a2b8",
        "#fd7e14", "#6f42c1", "#e83e8c", "#20c997"
    ];

    // ============================
    //   Remover não-negatividade
    // ============================
    const restricoesFiltradas = restricoes.filter(r => {
        const label = r.label.toLowerCase().replace(/\s+/g, '');

        if (label === 'x>=0' || label === 'y>=0' || label === 'x1>=0' || label === 'x2>=0')
            return false;

        if (r.constant === 0) {
            const coefs = Object.values(r.coefs)
                .map(c => Number(c))
                .filter(c => c !== 0);

            if (coefs.length === 1 && r.sinal === ">=")
                return false;
        }

        return true;
    });

    // ============================
    //   Linhas das Restrições
    // ============================
    restricoesFiltradas.forEach((r, index) => {

        // Garantir valores válidos
        const lbl = r.label ?? "Restrição";
        const sn  = r.sinal ?? "<=";
        const ct  = (r.constant !== undefined && r.constant !== null) ? r.constant : "";

        // Montar legenda sem undefined
        const legenda = ct === "" 
            ? `${lbl} (${sn})`
            : `${lbl} (${sn} ${ct})`;

        datasets.push({
            label: legenda,
            data: r.coords.map(p => ({ x: p.x, y: p.y })),
            borderColor: cores[index % cores.length],
            borderWidth: 2,
            showLine: true,
            fill: false,
            pointRadius: 0,
            tension: 0,
            type: 'line'
        });
    });


    // ============================
    //      Ponto Ótimo Inteiro
    // ============================
    if (pontoOtimo && Object.values(pontoOtimo).length >= 2) {
        const coords = Object.values(pontoOtimo);

        datasets.push({
            label: "Ponto ótimo inteiro",
            data: [{ x: coords[0], y: coords[1] }],
            type: 'scatter',
            pointRadius: 8,
            pointBackgroundColor: "#dc3545",
            pointBorderColor: "white",
            pointBorderWidth: 2
        });
    }

    // ============================
    //       Renderizar gráfico
    // ============================
    new Chart(ctx, {
        type: "scatter",
        data: { datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    max: limit,
                    title: { display: true, text: "X1" }
                },
                y: {
                    beginAtZero: true,
                    max: limit,
                    title: { display: true, text: "X2" }
                }
            },
            plugins: {
                legend: {
                    position: "top",
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                }
            }
        }
    });

});
</script>


@endif
@endpush
