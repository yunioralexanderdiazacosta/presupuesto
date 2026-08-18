<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Pago</title>
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
        .request-details {
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
        .character-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .character-normal { background-color: #6c757d; }
        .character-importante { background-color: #f5803e; }
        .character-urgente { background-color: #e63757; }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            background-color: #00d27a;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>💰 Solicitud de Pago</h1>
        <p>{{ $paymentRequest->number }}</p>
    </div>

    <div class="content">
        <p>Hola <strong>{{ $recipientName }}</strong>,</p>
        <p>Se te ha enviado una solicitud de pago para gestionar:</p>

        <div class="request-details">
            <div class="detail-row">
                <span class="detail-label">Folio:</span>
                <span>{{ $paymentRequest->number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Solicitante:</span>
                <span>{{ $paymentRequest->user->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha:</span>
                <span>{{ $paymentRequest->date->format('d/m/Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Carácter:</span>
                <span class="character-badge character-{{ $paymentRequest->character }}">{{ $paymentRequest->character_label }}</span>
            </div>
            @if($paymentRequest->costCenters->count())
            <div class="detail-row">
                <span class="detail-label">Centro(s) de Costo:</span>
                <span>{{ $paymentRequest->costCenters->pluck('name')->join(', ') }}</span>
            </div>
            @endif
            @if($paymentRequest->concept_observations)
            <div class="detail-row">
                <span class="detail-label">Concepto / Observaciones:</span>
                <span>{{ $paymentRequest->concept_observations }}</span>
            </div>
            @endif
        </div>

        <p style="text-align: center; color: #5e6e82; font-size: 14px;">
            Se adjunta un PDF con el detalle de la solicitud{{ $paymentRequest->file_path ? ' y la factura/imagen enviada' : '' }}.
        </p>

        <div class="button-container">
            <a href="{{ $resolveUrl }}" class="btn">✓ Marcar como Gestionada</a>
        </div>

        <p style="text-align: center; color: #6c757d; font-size: 12px;">
            Cualquiera de los destinatarios puede marcarla como gestionada una vez realizado el pago.<br>
            Este enlace expira en 7 días.
        </p>
    </div>

    <div class="footer">
        <p>Sistema de Gestión Presupuestaria</p>
        <p>Este es un correo automático, por favor no responder.</p>
    </div>
</body>
</html>
