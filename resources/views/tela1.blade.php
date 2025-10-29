@extends('layout.app')

@section('content')

    <div class="d-flex vh-100 align-items-center justify-content-center text-center">
        <div class="row">
            <div class="col-12">

                <img src="{{ asset('images/OrionLinearis.png') }}" alt="Logo Orion Linearis"
                    style="width: 250px; margin-bottom: 2rem;">

                <div>
                    <a href="{{ route('orion.definition') }}" class="btn btn-orion">
                        Iniciar
                    </a>
                </div>

                <p class="footer-text">
                    Sistema acadêmico inspirado no TORA - Pesquisa Operacional
                </p>
            </div>
        </div>
    </div>
@endsection
