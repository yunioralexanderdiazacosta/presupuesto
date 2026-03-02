<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechazar Rendición</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .container { background-color: white; max-width: 600px; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .warning-icon { font-size: 72px; color: #e63757; text-align: center; margin-bottom: 20px; }
        h1 { color: #e63757; margin-bottom: 10px; text-align: center; }
        .report-info { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #e63757; }
        .info-row { margin: 10px 0; display: flex; justify-content: space-between; }
        .info-label { font-weight: bold; color: #5e6e82; }
        .form-group { margin: 20px 0; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        textarea { width: 100%; padding: 12px; border: 1px solid #dee2e6; border-radius: 5px; font-family: Arial, sans-serif; font-size: 14px; resize: vertical; min-height: 100px; box-sizing: border-box; }
        .button-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { flex: 1; padding: 12px 30px; border: none; border-radius: 5px; font-weight: bold; font-size: 16px; cursor: pointer; text-decoration: none; text-align: center; }
        .btn-reject { background-color: #e63757; color: white; }
        .btn-cancel { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="warning-icon">⚠️</div>
        <h1>Rechazar Rendición</h1>

        <div class="report-info">
            <div class="info-row">
                <span class="info-label">Número:</span>
                <span>{{ $expenseReport->number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Rendidor:</span>
                <span>{{ $expenseReport->user->name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Monto Total:</span>
                <span>${{ number_format($expenseReport->items->sum('amount'), 0, ',', '.') }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('expense-reports.reject', $expenseReport) }}">
            @csrf
            <div class="form-group">
                <label for="rejection_reason">Motivo del rechazo (opcional):</label>
                <textarea
                    id="rejection_reason"
                    name="rejection_reason"
                    placeholder="Indica el motivo por el cual rechazas esta rendición..."
                >{{ old('rejection_reason') }}</textarea>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-reject">Confirmar Rechazo</button>
                <a href="javascript:window.close()" class="btn btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
