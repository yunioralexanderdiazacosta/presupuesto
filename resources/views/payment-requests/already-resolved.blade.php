<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud Ya Gestionada</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .container { background-color: white; max-width: 500px; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .icon { font-size: 72px; color: #2c7be5; margin-bottom: 20px; }
        h1 { color: #2c7be5; margin-bottom: 10px; font-size: 24px; }
        .number { font-size: 20px; color: #5e6e82; font-weight: bold; margin: 20px 0; }
        p { color: #5e6e82; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">ℹ️</div>
        <h1>Solicitud Ya Gestionada</h1>
        <p class="number">{{ $paymentRequest->number }}</p>
        <p>
            Esta solicitud de pago ya fue marcada como gestionada
            @if($paymentRequest->resolvedBy) por <strong>{{ $paymentRequest->resolvedBy->name }}</strong>@endif
            @if($paymentRequest->resolved_at) el {{ $paymentRequest->resolved_at->format('d/m/Y H:i') }}@endif.
        </p>
        <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
            Puedes cerrar esta ventana.
        </p>
    </div>
</body>
</html>
