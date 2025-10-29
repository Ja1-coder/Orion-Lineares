@extends('layout.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 text-center">
            <small class="text-uppercase" style="color: var(--orion-ciano);">ORION LINEARIS</small>
            <h2 class="mb-3">BEM-VINDO AO ORION LINEARIS</h2>
            <p class="lead mb-4" style="opacity: 0.8;">Sistema para resolver problemas com Simplex e outros métodos.</p>
        </div>

        <div class="col-12 col-md-10 col-lg-8">

            <form action="{{ route('orion.generate.table') }}" method="POST">
                @csrf <div class="card card-orion mb-4 p-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Defina o Problema</h5>
                        <div class="mb-3">
                            <label for="num_variaveis" class="form-label">Informe a quantidade de variáveis</label>
                            <input type="number" class="form-control" id="num_variaveis" name="num_variaveis" min="1"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="num_restricoes" class="form-label">Informe a quantidade de restrições</label>
                            <input type="number" class="form-control" id="num_restricoes" name="num_restricoes" min="1"
                                required>
                        </div>
                    </div>
                </div>

                <div class="card card-orion mb-4 p-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Escolha o método de Resolução</h5>
                        <div class="mb-3">
                            <label for="metodo" class="form-label">Forma de Resolução</label>
                            <select class="form-select" id="metodo" name="metodo">
                                <option value="simplex_tabular">Simplex Tabular</option>
                                <option value="grafico" disabled>Método Gráfico (em breve)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-orion">Avançar</button>
                </div>
            </form>
        </div>
    </div>
@endsection