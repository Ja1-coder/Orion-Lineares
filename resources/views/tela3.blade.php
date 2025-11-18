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
            <i class="fa-solid fa-house"></i> Voltar ao Início </a>
    </nav>
@endsection


@section('content')
    <div class="row justify-content-center py-4 py-md-5">

        <div class="col-12 text-center mb-4">
            <h2 class="mb-3" style="font-weight: 600;">DEFININDO AS RESTRIÇÕES</h2>
        </div>

        <div class="col-12 col-md-11 col-lg-10">
            <form action="{{ route('orion.solve') }}" method="POST">
                @csrf

                <input type="hidden" name="num_variaveis" value="{{ $vars }}">
                <input type="hidden" name="num_restricoes" value="{{ $rests }}">
                <input type="hidden" name="tipo_problema" id="tipo_problema" value="maximizar">

                <div class="card card-light p-3 p-md-4">

                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                        <h5 class="mb-0 me-3">Informe o tipo de problema</h5>
                        <div class="btn-toggle-group">
                            <button type="button" id="btn-max" class="btn-toggle-option active">Maximizar</button>
                            <button type="button" id="btn-min" class="btn-toggle-option">Minimizar</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-orion-light align-middle" style="min-width: 700px;">
                            <thead>
                                <tr>
                                    <th style="width: 5%;"></th>
                                    @for ($j = 1; $j <= $vars; $j++)
                                        <th class="text-center">x{{ $j }}</th>
                                    @endfor
                                    <th class="text-center">Sinais</th>
                                    <th class="text-center">R.H.S</th>
                                    <th class="text-center" style="width: 10%;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Z</strong></td>
                                    @for ($j = 1; $j <= $vars; $j++)
                                        <td>
                                            <input type="text" name="z[]" class="form-control text-center">
                                        </td>
                                    @endfor
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>

                                @for ($i = 1; $i <= $rests; $i++)
                                    <tr>
                                        <td><strong>R.{{ $i }}</strong></td>
                                        @for ($j = 1; $j <= $vars; $j++)
                                            <td>
                                                <input type="text" name="restricoes[{{ $i }}][coefs][]"
                                                    class="form-control text-center" >
                                            </td>
                                        @endfor
                                        <td>
                                            <select name="restricoes[{{ $i }}][sinal]" class="form-select">
                                                <option value=">=">>=</option>
                                                <option value="<="><=</option>
                                                <option value="=">=</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="restricoes[{{ $i }}][rhs]" class="form-control text-center"
                                                >
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-delete-row">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center pt-3">
                        <button type="button" class="btn btn-add-restricao">
                            <i class="fa-solid fa-plus me-2"></i>Adicionar Restrição
                        </button>
                    </div>

                    <div class="text-center pt-3">
                        <button type="submit" class="btn btn-orion">Resolver</button>
                    </div>

                </div>

            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const btnMax = document.getElementById('btn-max');
        const btnMin = document.getElementById('btn-min');
        const tipoProblemaInput = document.getElementById('tipo_problema');

        btnMax.addEventListener('click', () => {
            tipoProblemaInput.value = 'maximizar';
            btnMax.classList.add('active');
            btnMin.classList.remove('active');
        });

        btnMin.addEventListener('click', () => {
            tipoProblemaInput.value = 'minimizar';
            btnMin.classList.add('active');
            btnMax.classList.remove('active');
        });

        document.addEventListener('DOMContentLoaded', () => {

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.btn-delete-row')) return;

                const btn = e.target.closest('.btn-delete-row');
                const row = btn.closest('tr');

                if (!row) return;

                row.querySelectorAll('input').forEach(input => {
                    input.value = 0;
                });

                const select = row.querySelector('select');
                if (select) {
                    select.value = ">=";
                }
            });

        });

    </script>
@endpush