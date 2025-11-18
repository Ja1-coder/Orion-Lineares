@extends('layout.app')

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
        <i class="fa-solid fa-house"></i>
        Voltar ao Início
    </a>
</nav>


@section('content')
    <div class="row justify-content-center py-4 py-md-5">

        <div class="col-12 col-md-10 col-lg-8 text-center mb-4">
            <h2 class="mb-3" style="font-weight: 600;">BEM-VINDO AO ORION LINEARIS</h2>
            <p class="lead" style="opacity: 0.8; font-weight: 300;">
                Sistema para resolver problemas com Simplex e outros métodos (A Solução dos seus problemas)
            </p>
        </div>

        <div class="col-12 col-md-10 col-lg-8">

            <form action="{{ route('orion.generate.table') }}" method="POST">
                @csrf

                <div class="card card-light mb-4">
                    <h5 class="mb-4">Defina o Problema</h5>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label for="num_variaveis" class="form-label mb-0">Informe a quantidade de variáveis:</label>
                        <input type="number" class="form-control form-control-small-box" id="num_variaveis"
                            name="num_variaveis" min="1" value="2" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <label for="num_restricoes" class="form-label mb-0">Informe a quantidade de restrições:</label>
                        <input type="number" class="form-control form-control-small-box" id="num_restricoes"
                            name="num_restricoes" min="1" value="3" required>
                    </div>
                </div>

                <div class="card card-light mb-4">
                    <h5 class="mb-4">Escolha o método de Resolução</h5>

                    <div class="mb-3">
                        <label for="metodo" class="form-label">Forma de Resolução</label>
                        <select class="form-select" id="metodo" name="metodo" required>
                            <option value="simplex_tabular" selected>Simplex Tabular (com Gráfico 2D)</option>
                        </select>
                        <div class="form-text text-white-50">
                            * O gráfico será gerado automaticamente para problemas com 2 variáveis.
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-orion mt-1">Avançar</button>
                </div>
            </form>
        </div>
    </div>
@endsection