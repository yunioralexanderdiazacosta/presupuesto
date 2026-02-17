<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden Ya Procesada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: white;
            max-width: 600px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .icon {
            font-size: 72px;
            margin-bottom: 20px;
        }
        .icon.info {
            color: #2c7be5;
        }
        h1 {
            color: #2c7be5;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .order-number {
            font-size: 20px;
            color: #5e6e82;
            font-weight: bold;
            margin: 20px 0;
        }
        .message {
            font-size: 18px;
            color: #333;
            margin: 20px 0;
            line-height: 1.6;
        }
        .status-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2c7be5;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 5px 0;
        }
        .detail-label {
            font-weight: bold;
            color: #5e6e82;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .status-approved {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-sent {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background-color: #d6d8db;
            color: #383d41;
        }
        .note {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon info">ℹ️</div>
        <h1>Esta Orden Ya Fue Procesada</h1>
        <p class="order-number">{{ $purchaseOrder->order_number }}</p>

        <div class="message">
            {{ $message }}
        </div>

        <div class="status-info">
            <div class="detail-row">
                <span class="detail-label">Estado Actual:</span>
                <span class="status-badge status-{{ $purchaseOrder->status }}">
                    {{ $purchaseOrder->status_label }}
                </span>
            </div>
            
            @if($purchaseOrder->approvedBy)
            <div class="detail-row">
                <span class="detail-label">{{ $purchaseOrder->status === 'approved' ? 'Aprobado por:' : 'Procesado por:' }}</span>
                <span>{{ $purchaseOrder->approvedBy->name }}</span>
            </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">Proveedor:</span>
                <span>{{ $purchaseOrder->supplier->name ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Monto Total:</span>
                <span>${{ number_format($purchaseOrder->total, 0, ',', '.') }}</span>
            </div>
        </div>

        @if($purchaseOrder->status === 'approved')
            <p style="color: #00d27a; font-weight: bold; margin-top: 20px;">
                ✓ Esta orden ya fue aprobada y está siendo procesada.
            </p>
        @elseif($purchaseOrder->status === 'rejected')
            <p style="color: #e63757; font-weight: bold; margin-top: 20px;">
                ✗ Esta orden ya fue rechazada.
            </p>
        @endif

        <div class="note">
            <p><strong>Nota:</strong> Los enlaces de aprobación/rechazo solo funcionan una vez.</p>
            <p>Si necesitas realizar algún cambio, contacta al administrador del sistema.</p>
            <p style="margin-top: 15px;">Puedes cerrar esta ventana.</p>
        </div>
    </div>
</body>
</html>
