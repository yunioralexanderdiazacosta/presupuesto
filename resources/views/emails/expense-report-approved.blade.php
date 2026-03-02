<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendición Aprobada</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #00d27a; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; }
        .report-details { background-color: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #00d27a; }
        .detail-row { margin: 10px 0; display: flex; justify-content: space-between; }
        .detail-label { font-weight: bold; color: #5e6e82; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Rendición Aprobada</h1>
    </div>
    <div class="content">
        <p>Hola <strong>{{ $expenseReport->user->name ?? '' }}</strong>,</p>
        <p>Tu rendición de gastos ha sido <strong>aprobada</strong> por <strong>{{ $approverName }}</strong>.</p>

        <div class="report-details">
            <div class="detail-row">
                <span class="detail-label">Número:</span>
                <span>{{ $expenseReport->number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Documentos:</span>
                <span>{{ $expenseReport->items->count() }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Monto Total:</span>
                <span>${{ number_format($expenseReport->items->sum('amount'), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
    <div class="footer">
        <p>Sistema de Gestión Presupuestaria</p>
        <p>Este es un correo automático, por favor no responder.</p>
    </div>
</body>
</html>
