<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendición Rechazada</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .container { background-color: white; max-width: 500px; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .icon { font-size: 72px; color: #e63757; margin-bottom: 20px; }
        h1 { color: #e63757; margin-bottom: 10px; }
        .report-number { font-size: 24px; color: #2c7be5; font-weight: bold; margin: 20px 0; }
        p { color: #5e6e82; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">❌</div>
        <h1>Rendición Rechazada</h1>
        <p class="report-number">{{ $expenseReport->number }}</p>
        <p>
            La rendición de gastos ha sido rechazada.<br>
            El rendidor ha sido notificado por correo electrónico.
        </p>
        <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
            Puedes cerrar esta ventana.
        </p>
    </div>
</body>
</html>
