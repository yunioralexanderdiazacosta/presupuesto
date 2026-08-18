<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Pago Gestionada</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #00d27a; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; }
        .request-details { background-color: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #00d27a; }
        .detail-row { margin: 10px 0; display: flex; justify-content: space-between; }
        .detail-label { font-weight: bold; color: #5e6e82; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Solicitud Gestionada</h1>
        <p>{{ $paymentRequest->number }}</p>
    </div>

    <div class="content">
        <p>Hola <strong>{{ $paymentRequest->user->name ?? '' }}</strong>,</p>
        <p>Tu solicitud de pago fue marcada como gestionada:</p>

        <div class="request-details">
            <div class="detail-row">
                <span class="detail-label">Folio:</span>
                <span>{{ $paymentRequest->number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Gestionada por:</span>
                <span>{{ $resolvedByName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha de gestión:</span>
                <span>{{ $paymentRequest->resolved_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <p style="color: #5e6e82; font-size: 14px;">
            Recuerda revisar el registro del pago en el módulo de Pago de Facturas cuando corresponda.
        </p>
    </div>

    <div class="footer">
        <p>Sistema de Gestión Presupuestaria</p>
        <p>Este es un correo automático, por favor no responder.</p>
    </div>
</body>
</html>
