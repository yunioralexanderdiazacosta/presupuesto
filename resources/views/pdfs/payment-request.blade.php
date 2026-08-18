<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Pago {{ $paymentRequest->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #333; padding: 15px; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #2c7be5; padding-bottom: 8px; }
        .header h1 { font-size: 18px; color: #2c7be5; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #666; }
        .info-section { margin-bottom: 10px; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #2c7be5; }
        .info-row { margin: 6px 0; }
        .info-label { font-weight: bold; color: #666; text-transform: uppercase; font-size: 8px; display: block; }
        .info-value { font-size: 11px; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 9px; font-weight: bold; color: white; }
        .badge-normal { background-color: #6c757d; }
        .badge-importante { background-color: #f5803e; }
        .badge-urgente { background-color: #e63757; }
        .badge-pendiente { background-color: #3b7ddd; }
        .badge-gestionada { background-color: #00d27a; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Solicitud de Pago {{ $paymentRequest->number }}</h1>
        <p>{{ $paymentRequest->team->name ?? '' }}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Solicitante</span>
            <span class="info-value">{{ $paymentRequest->user->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha</span>
            <span class="info-value">{{ $paymentRequest->date->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Carácter</span>
            <span class="badge badge-{{ $paymentRequest->character }}">{{ $paymentRequest->character_label }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado</span>
            <span class="badge badge-{{ $paymentRequest->status }}">{{ $paymentRequest->status_label }}</span>
        </div>
        @if($paymentRequest->status === 'gestionada')
        <div class="info-row">
            <span class="info-label">Gestionada por</span>
            <span class="info-value">{{ $paymentRequest->resolvedBy->name ?? 'N/A' }} el {{ $paymentRequest->resolved_at?->format('d/m/Y H:i') }}</span>
        </div>
        @endif
        @if($paymentRequest->costCenters->count())
        <div class="info-row">
            <span class="info-label">Centro(s) de Costo</span>
            <span class="info-value">{{ $paymentRequest->costCenters->pluck('name')->join(', ') }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Destinatarios</span>
            <span class="info-value">{{ $paymentRequest->recipients->pluck('name')->join(', ') }}</span>
        </div>
        @if($paymentRequest->concept_observations)
        <div class="info-row">
            <span class="info-label">Concepto / Observaciones</span>
            <span class="info-value">{{ $paymentRequest->concept_observations }}</span>
        </div>
        @endif
        @if($paymentRequest->files->count())
        <div class="info-row">
            <span class="info-label">Archivos Adjuntos</span>
            <span class="info-value">{{ $paymentRequest->files->pluck('original_name')->join(', ') }}</span>
        </div>
        @endif
    </div>
</body>
</html>
