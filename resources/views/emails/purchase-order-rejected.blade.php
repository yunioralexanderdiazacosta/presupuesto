<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Compra Rechazada</title>
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
            background-color: #e63757;
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
        .warning-icon {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
        }
        .order-details {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid #e63757;
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
        .detail-value {
            color: #333;
        }
        .reason-box {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✗ Orden de Compra Rechazada</h1>
    </div>

    <div class="content">
        <div class="warning-icon">⚠️</div>

        <p>Hola {{ $purchaseOrder->requestedBy->name ?? '' }},</p>
        
        <p>Lamentamos informarte que tu orden de compra ha sido rechazada.</p>

        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">Número de Orden:</span>
                <span class="detail-value">{{ $purchaseOrder->order_number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Proveedor:</span>
                <span class="detail-value">{{ $purchaseOrder->supplier->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Monto Total:</span>
                <span class="detail-value">${{ number_format($purchaseOrder->total, 0, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Rechazado por:</span>
                <span class="detail-value">{{ $rejectedByName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha de Rechazo:</span>
                <span class="detail-value">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        @if($rejectionReason)
        <div class="reason-box">
            <strong>Motivo del Rechazo:</strong><br>
            {{ $rejectionReason }}
        </div>
        @endif

        <p>Puedes modificar la orden y volver a enviarla para aprobación desde el sistema.</p>
    </div>

    <div class="footer">
        <p>Sistema de Gestión Presupuestaria</p>
        <p>Este es un correo automático, por favor no responder.</p>
    </div>
</body>
</html>
