<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Emails - Purchase Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #2c7be5;
            border-bottom: 3px solid #2c7be5;
            padding-bottom: 10px;
        }
        .info-box {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .email-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .email-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.2s;
        }
        .email-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .email-card h3 {
            margin-top: 0;
            color: #333;
        }
        .email-card p {
            color: #666;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: opacity 0.2s;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .btn-primary {
            background-color: #2c7be5;
            color: white;
        }
        .btn-success {
            background-color: #00d27a;
            color: white;
        }
        .order-info {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .code {
            background-color: #f8f9fa;
            padding: 10px;
            border-left: 3px solid #2c7be5;
            font-family: monospace;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>🧪 Test de Emails - Purchase Orders</h1>

    <div class="info-box">
        <strong>📧 Configuración Actual:</strong><br>
        MAIL_MAILER: <code>{{ config('mail.default') }}</code><br>
        @if(config('mail.default') === 'log')
            ➡️ Los emails se guardan en: <code>storage/logs/laravel.log</code>
        @else
            ➡️ Los emails se envían vía SMTP
        @endif
    </div>

    @if($purchaseOrder)
    <div class="order-info">
        <strong>📦 Orden de Prueba:</strong><br>
        Número: <strong>{{ $purchaseOrder->order_number }}</strong><br>
        Proveedor: {{ $purchaseOrder->supplier->name ?? 'N/A' }}<br>
        Total: ${{ number_format($purchaseOrder->total, 0, ',', '.') }}<br>
        Items: {{ $purchaseOrder->items->count() }}
    </div>
    @else
    <div class="order-info" style="background-color: #f8d7da; border-color: #e63757;">
        <strong>⚠️ No hay órdenes de compra</strong><br>
        Crea una orden de compra primero para poder probar los emails.
    </div>
    @endif

    <h2>Tipos de Email Disponibles</h2>

    <div class="email-grid">
        <div class="email-card">
            <h3>🔔 Pending Approval</h3>
            <p>Email enviado a aprobadores cuando una orden cambia a estado "pending".</p>
            <p><small>Incluye: Botones de Aprobar/Rechazar con links firmados.</small></p>
            <a href="{{ route('test.email.preview', 'pending') }}" class="btn btn-primary" target="_blank">👁️ Ver HTML</a>
            <a href="{{ route('test.email.send', 'pending') }}" class="btn btn-success" onclick="return confirm('¿Enviar email de prueba?')">📤 Enviar</a>
        </div>

        <div class="email-card">
            <h3>✅ Approved</h3>
            <p>Email enviado al solicitante cuando su orden es aprobada.</p>
            <p><small>Incluye: Detalles de la orden y quién la aprobó.</small></p>
            <a href="{{ route('test.email.preview', 'approved') }}" class="btn btn-primary" target="_blank">👁️ Ver HTML</a>
            <a href="{{ route('test.email.send', 'approved') }}" class="btn btn-success" onclick="return confirm('¿Enviar email de prueba?')">📤 Enviar</a>
        </div>

        <div class="email-card">
            <h3>❌ Rejected</h3>
            <p>Email enviado al solicitante cuando su orden es rechazada.</p>
            <p><small>Incluye: Motivo del rechazo y detalles de la orden.</small></p>
            <a href="{{ route('test.email.preview', 'rejected') }}" class="btn btn-primary" target="_blank">👁️ Ver HTML</a>
            <a href="{{ route('test.email.send', 'rejected') }}" class="btn btn-success" onclick="return confirm('¿Enviar email de prueba?')">📤 Enviar</a>
        </div>
    </div>

    <h2>🔧 Configuración para Testing Local</h2>

    <div class="code">
# En .env (desarrollo local)<br>
MAIL_MAILER=log<br>
<br>
# Los emails aparecerán en storage/logs/laravel.log
    </div>

    <h2>📝 Instrucciones de Prueba</h2>

    <ol>
        <li><strong>Ver HTML:</strong> Haz clic en "👁️ Ver HTML" para ver cómo se ve el email en el navegador</li>
        <li><strong>Enviar Prueba:</strong> Haz clic en "📤 Enviar" para generar el email (se guarda en logs)</li>
        <li><strong>Revisar Logs:</strong> Abre <code>storage/logs/laravel.log</code> y busca el email generado</li>
        <li><strong>Probar Links:</strong> Los links de aprobar/rechazar funcionan solo con emails reales (requiere SMTP configurado)</li>
    </ol>

    <h2>🚀 Testing en Producción</h2>

    <div class="code">
# En .env (producción)<br>
MAIL_MAILER=smtp<br>
MAIL_HOST=smtp.gmail.com<br>
MAIL_PORT=587<br>
MAIL_USERNAME=tu-email@gmail.com<br>
MAIL_PASSWORD=tu-contraseña-app<br>
MAIL_ENCRYPTION=tls<br>
MAIL_FROM_ADDRESS=tu-email@gmail.com<br>
MAIL_FROM_NAME="${APP_NAME}"
    </div>

    <p style="margin-top: 30px; text-align: center; color: #666;">
        <strong>⚠️ Recuerda eliminar estas rutas de testing en producción</strong>
    </p>
</body>
</html>
