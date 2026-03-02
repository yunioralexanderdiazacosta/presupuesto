<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendición Pendiente de Aprobación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2c7be5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border: 1px solid #dee2e6;
        }
        .report-details {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid #2c7be5;
        }
        .detail-row {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
        }
        .detail-label {
            font-weight: bold;
            color: #5e6e82;
        }
        .items-table {
            width: 100%;
            background-color: white;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #2c7be5;
            color: white;
            padding: 10px;
            text-align: left;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .btn-approve {
            background-color: #00d27a;
            color: white;
        }
        .btn-reject {
            background-color: #e63757;
            color: white;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
        .total-row {
            font-weight: bold;
            font-size: 16px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Rendición de Gastos</h1>
        <p>Requiere tu aprobación</p>
    </div>

    <div class="content">
        <p>Hola <strong>{{ $approverName }}</strong>,</p>
        <p>Se ha enviado una rendición de gastos que requiere tu aprobación:</p>

        <div class="report-details">
            <div class="detail-row">
                <span class="detail-label">Número:</span>
                <span>{{ $expenseReport->number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Rendidor:</span>
                <span>{{ $expenseReport->user->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha:</span>
                <span>{{ $expenseReport->created_at->format('d/m/Y') }}</span>
            </div>
            @if($expenseReport->description)
            <div class="detail-row">
                <span class="detail-label">Descripción:</span>
                <span>{{ $expenseReport->description }}</span>
            </div>
            @endif
        </div>

        <h3>Detalle de Documentos:</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenseReport->items as $item)
                <tr>
                    <td>{{ $item->date->format('d/m/Y') }}</td>
                    <td>{{ $item->supplier->name ?? 'N/A' }}</td>
                    <td>{{ $item->description ?? ($item->product->name ?? '—') }}</td>
                    <td>${{ number_format($item->amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">TOTAL:</td>
                    <td>${{ number_format($expenseReport->items->sum('amount'), 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="button-container">
            <a href="{{ $approveUrl }}" class="btn btn-approve">✓ Aprobar Rendición</a>
            <a href="{{ $rejectUrl }}" class="btn btn-reject">✗ Rechazar Rendición</a>
        </div>

        <p style="text-align: center; color: #6c757d; font-size: 12px;">
            Estos enlaces expiran en 48 horas
        </p>
    </div>

    <div class="footer">
        <p>Sistema de Gestión Presupuestaria</p>
        <p>Este es un correo automático, por favor no responder.</p>
    </div>
</body>
</html>
