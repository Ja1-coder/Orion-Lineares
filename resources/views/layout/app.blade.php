<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orion Linearis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --orion-azul-escuro: #0a1f3d;
            --orion-azul-medio: #1a4a5c;
            --orion-ciano: #00B4D8;
            --orion-branco: #FFFFFF;
            --orion-cinza-claro: #F0F0F0;
            --orion-gradiente-fim: #2a7a7a;
            --orion-texto-escuro: #0a1f3d;
            --orion-cabecalho-tabela: #3eb8a8;
            --orion-celulas-tabela: #e8f4f3;
            --orion-linhas-tabela: #d1d5db;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom, var(--orion-azul-escuro) 0%, var(--orion-gradiente-fim) 100%);
            color: var(--orion-branco);
            min-height: 100vh;
        }

        .header-orion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            width: 100%;
        }

        .header-orion .logo-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--orion-branco);
        }

        .header-orion .logo-link svg {
            margin-right: 0.75rem;
            opacity: 0.8;
        }

        .header-orion .logo-link-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .header-orion .logo-link-text span:first-child {
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .header-orion .logo-link-text span:last-child {
            font-weight: 300;
            font-size: 1.0rem;
            letter-spacing: 2px;
        }

        .btn-back {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            color: var(--orion-branco);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: var(--orion-branco);
            color: var(--orion-texto-escuro);
        }

        .btn-back i {
            margin-right: 0.5rem;
        }


        .btn-orion {
            background-color: var(--orion-gradiente-fim);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem 3rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            letter-spacing: 1px;
            font-size: 1rem;
        }

        .btn-orion:hover {
            background-color: var(--orion-branco);
            color: var(--orion-azul-escuro);
            transform: translateY(-3px);
            box-shadow: 0 5px 12px rgba(0, 180, 216, 0.4);
        }

        .footer-text {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0.7;
            font-size: 0.85rem;
            font-weight: 300;
            width: 100%;
            padding: 0 1rem;
        }

        .card-orion {
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            text-align: left;
        }

        .card-light {
            background-color: var(--orion-branco);
            color: var(--orion-texto-escuro);
            border: none;
            border-radius: 10px;
            text-align: left;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .card-light h5 {
            font-weight: 600;
            color: var(--orion-azul-escuro);
        }

        .card-light .form-label {
            color: var(--orion-azul-medio);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .card-light .form-control,
        .card-light .form-select {
            background-color: var(--orion-cinza-claro);
            border: 1px solid #ddd;
            color: var(--orion-texto-escuro);
            border-radius: 8px;
        }

        .card-light .form-control:focus,
        .card-light .form-select:focus {
            background-color: var(--orion-branco);
            border-color: var(--orion-gradiente-fim);
            box-shadow: 0 0 0 0.25rem rgba(42, 122, 122, 0.3);
            color: var(--orion-texto-escuro);
        }

        .card-light .form-select option {
            background-color: var(--orion-branco);
            color: var(--orion-texto-escuro);
        }

        .form-control-small-box {
            width: 80px !important;
            text-align: center;
        }

        .table-orion {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--orion-branco);
        }

        .table-orion th {
            color: var(--orion-ciano);
        }

        .table-orion input,
        .table-orion select {
            background-color: rgba(0, 59, 111, 0.7);
            color: var(--orion-branco);
            border: 1px solid var(--orion-azul-medio);
            width: 100%;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
        }

        .btn-toggle-group {
            display: flex;
            gap: 0.5rem;
        }

        .btn-toggle-option {
            background: var(--orion-branco);
            border: 2px solid #DDE2E5;
            color: var(--orion-texto-escuro);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-toggle-option.active {
            background-color: var(--orion-gradiente-fim);
            color: var(--orion-branco);
            border-color: var(--orion-gradiente-fim); /* Borda da mesma cor do fundo */
            box-shadow: 0 2px 5px rgba(42, 122, 122, 0.4);
        }

        .table-orion-light {
            color: var(--orion-texto-escuro);
        }

        .table-orion-light th {
            color: var(--orion-azul-medio);
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .table-orion-light td {
            vertical-align: middle;
        }

        .table-orion-light strong {
            color: var(--orion-azul-escuro);
            font-size: 1.1rem;
        }

        .table-orion-light .form-control,
        .table-orion-light .form-select {
            background-color: var(--orion-cinza-claro);
            border: 1px solid #ddd;
            color: var(--orion-texto-escuro);
            font-weight: 500;
        }

        .table-orion-light .form-control:focus,
        .table-orion-light .form-select:focus {
            background-color: var(--orion-branco);
            border-color: var(--orion-gradiente-fim);
            box-shadow: 0 0 0 0.25rem rgba(42, 122, 122, 0.3);
            color: var(--orion-texto-escuro);
        }

        .btn-add-restricao {
            background-color: var(--orion-azul-escuro);
            color: var(--orion-branco);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-add-restricao:hover {
            background-color: var(--orion-azul-medio);
            transform: translateY(-2px);
        }

        .btn-delete-row {
            background: #ffebee;
            color: #d32f2f;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-delete-row:hover {
            background: #d32f2f;
            color: var(--orion-branco);
            transform: scale(1.1);
        }
    </style>
</head>

<body>

    @yield('header')

    <div class="container">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
