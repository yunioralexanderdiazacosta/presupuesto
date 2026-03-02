<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendición Ya Procesada</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .container { background-color: white; max-width: 500px; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .icon { font-size: 72px; color: #2c7be5; margin-bottom: 20px; }
        h1 { color: #2c7be5; margin-bottom: 10px; font-size: 24px; }
        .report-number { font-size: 20px; color: #5e6e82; font-weight: bold; margin: 20px 0; }
        .message { font-size: 18px; color: #333; margin: 20px 0; line-height: 1.6; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; color: white; margin: 10px 0; }
        .status-aprobada { background-color: #00d27a; }
        .status-rechazada { background-color: #e63757; }
        .status-borrador { background-color: #6c757d; }
        .status-pagada { background-color: #2c7be5; }
        p { color: #5e6e82; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">ℹ️</div>
        <h1>Rendición Ya Procesada</h1>
        <p class="report-number">{{ $expenseReport->number }}</p>
        <p class="message">{{ $message }}</p>
        <p>
            <span class="status-badge status-{{ $expenseReport->status }}">
                {{ ucfirst($expenseReport->status) }}
            </span>
        </p>
        <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
            No se puede {{ $action }} porque ya fue procesada.<br>
            Puedes cerrar esta ventana.
        </p>
    </div>
</body>
</html>
