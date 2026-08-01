<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IUDocs</title>
    <style>
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, sans-serif;
            background: #FEFCF8;
            color: #241C12;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .card { padding: 2rem; }
        .spinner {
            width: 28px; height: 28px;
            margin: 0 auto 1rem;
            border: 3px solid #DBEBC7;
            border-top-color: #4F8A38;
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { font-size: .9rem; color: #78716C; }
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner"></div>
        <p>Listo. Ya podés cerrar esta ventana.</p>
    </div>

    <script>
        (function () {
            var target = @json($redirect);
            try {
                if (window.opener && !window.opener.closed) {
                    window.opener.location.href = target;
                }
            } catch (e) {
                // Si por algún motivo no se puede avisar al opener, navegamos acá mismo
                window.location.href = target;
                return;
            }
            window.close();
        })();
    </script>
</body>
</html>
