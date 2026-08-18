<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso no disponible</title>
    <link href="{{ asset('assets/css/theme.css') }}" rel="stylesheet">
</head>
<body class="bg-100">
    <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card shadow-sm" style="max-width: 480px; width: 100%;">
            <div class="card-body text-center p-4 p-sm-5">
                <div class="mb-3">
                    <span class="fas fa-lock text-warning" style="font-size: 3.5rem;"></span>
                </div>
                <h4 class="mb-3">Acceso no disponible</h4>
                <p class="text-700 mb-4">
                    {{ $exception->getMessage() ?: 'No tienes permisos para acceder a esta sección. Contacta al administrador del sistema para más información.' }}
                </p>
                <a href="{{ url('/') }}" class="btn btn-falcon-primary btn-sm">
                    <span class="fas fa-arrow-left me-1"></span> Volver al inicio
                </a>
            </div>
        </div>
    </div>
</body>
</html>
