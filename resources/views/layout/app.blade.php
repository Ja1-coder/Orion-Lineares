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

    <style>
        :root {
            --orion-azul-escuro: #0a1f3d;
            --orion-azul-medio: ##1a4a5c;
            --orion-ciano: #00B4D8;
            --orion-branco: #FFFFFF;
            --orion-cinza-claro: #F0F0F0;
            --orion-gradiente-fim: #2a7a7a;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom, var(--orion-azul-escuro) 0%, var(--orion-gradiente-fim) 100%);
            color: var(--orion-branco);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
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
            margin-top: 2.5rem;
        }

        .btn-orion:hover {
            background-color: var(--orion-branco);
            color: var(--orion-azul-escuro);
            transform: translateY(-3px);
            box-shadow: 0 5px 12px rgba(0, 180, 216, 0.4);
        }

        /* Texto do rodapé posicionado absolutamente */
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

        /* --- Estilos de suporte (das outras telas) --- */
        .card-orion {
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .form-control,
        .form-select {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid var(--orion-ciano);
            color: var(--orion-branco);
            border-radius: 8px;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-control:focus,
        .form-select:focus {
            background-color: rgba(255, 255, 255, 0.3);
            border-color: var(--orion-branco);
            box-shadow: 0 0 0 0.25rem rgba(0, 180, 216, 0.5);
            color: var(--orion-branco);
        }

        .form-select option {
            background-color: var(--orion-azul-medio);
            color: var(--orion-branco);
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

        .table-orion input:focus,
        .table-orion select:focus {
            outline: none;
            border-color: var(--orion-ciano);
            box-shadow: 0 0 5px rgba(0, 180, 216, 0.5);
        }
    </style>
</head>

<body>

    <div class="container">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>

</html>