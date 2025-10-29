@extends('layout.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 text-center mb-4">
            <small class="text-uppercase" style="color: var(--orion-ciano);">ORION LINEARIS</small>
            <h2 class="mb-3">DEFININDO AS RESTRIÇÕES</h2>
        </div>

        <div class="col-12">
            <form action="{{ route('orion.solve') }}" method="POST">
                @csrf

                <input type="hidden" name="num_variaveis" value="{{ $vars }}">
                <input type="hidden" name="num_restricoes" value="{{ $rests }}">
                <input type="hidden" name="tipo_problema" id="tipo_problema" value="maximizar">

                <div class="card card-orion p-3 p-md-4">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">Informe o tipo de problema</h5>
                            <div>
                                <button type="button" id="btn-max" class="btn btn-orion">Maximizar</button>
                                <button type="button" id="btn-min" class="btn btn-outline-light">Minimizar</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-orion align-middle">
                                <thead>
                                    <tr>
                                        <th></th> @for ($j = 1; $j <= $vars; $j++)
                                            <th class="text-center">x{{ $j }}</th>
                                        @endfor

                                        <th class="text-center">Sinais</th>
                                        <th class="text-center">R.H.S</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Z</strong></td>

                                        @for ($j = 1; $j <= $vars; $j++)
                                            <td>
                                                <input type="text" name="z[]" class="form-control text-center" placeholder="0">
                                            </td>
                                        @endfor

                                        <td></td>
                                        <td></td>
                                    </tr>

                                    @for ($i = 1; $i <= $rests; $i++)
                                        <tr>
                                            <td><strong>R.{{ $i }}</strong></td>

                                            @for ($j = 1; $j <= $vars; $j++)
                                                <td>
                                                    <input type="text" name="restricoes[{{ $i }}][coefs][]"
                                                        class="form-control text-center" placeholder="0">
                                                </td>
                                            @endfor

                                            <td>
                                                <select name="restricoes[{{ $i }}][sinal]" class="form-select">
                                                    <option value="<=">
                                                        <=< /option>
                                                    <option value="=">=</option>
                                                    <option value=">=">>=</option>
                                                </select>
                                            </td>

                                            <td>
                                                <input type="text" name="restricoes[{{ $i }}][rhs]"
                                                    class="form-control text-center" placeholder="0">
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('orion.definition') }}" class="btn btn-outline-light me-2">Voltar</a>
                    <button type="submit" class="btn btn-orion">Resolver Problema</button>
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
            btnMax.classList.replace('btn-outline-light', 'btn-orion');
            btnMin.classList.replace('btn-orion', 'btn-outline-light');
        });

        btnMin.addEventListener('click', () => {
            tipoProblemaInput.value = 'minimizar';
            btnMin.classList.replace('btn-outline-light', 'btn-orion');
            btnMax.classList.replace('btn-orion', 'btn-outline-light');
        });
    </script>
@endpush