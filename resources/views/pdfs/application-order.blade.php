<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Aplicación #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 24px;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 12px;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        
        .info-section h2 {
            font-size: 14px;
            color: #007bff;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .info-value {
            color: #333;
            font-size: 11px;
            margin-top: 2px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        
        .status-pendiente {
            background-color: #ffc107;
            color: #000;
        }
        
        .status-en_proceso {
            background-color: #17a2b8;
            color: #fff;
        }
        
        .status-completada {
            background-color: #28a745;
            color: #fff;
        }
        
        .status-cancelada {
            background-color: #dc3545;
            color: #fff;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        
        table th {
            background-color: #007bff;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        table td {
            padding: 6px 8px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        table tfoot {
            background-color: #e9ecef;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .badge-primary {
            background-color: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #000;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        
        .signature-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            page-break-inside: avoid;
        }
        
        .signature-box {
            text-align: center;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        
        .signature-label {
            font-size: 10px;
            font-weight: bold;
            color: #666;
        }
        
        .total-highlight {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 8px;
        }
        
        @media print {
            body {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>ORDEN DE APLICACIÓN</h1>
        <p>{{ $order->team->name ?? 'Equipo' }} - Temporada: {{ $order->season->name ?? 'N/A' }}</p>
        <p style="font-size: 10px; color: #999;">Orden #{{ $order->id }} - Fecha de emisión: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Información General -->
    <div class="info-section">
        <h2>📋 Información General</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Fecha de Aplicación:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Estado:</div>
                <div class="info-value">
                    @php
                        $statusLabels = [
                            'pendiente' => 'Pendiente',
                            'en_proceso' => 'En Proceso',
                            'completada' => 'Completada',
                            'cancelada' => 'Cancelada'
                        ];
                        $statusClass = 'status-' . $order->status;
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Mojamiento:</div>
                <div class="info-value">{{ number_format($order->mojamiento, 2, ',', '.') }} Litros</div>
            </div>
            <div class="info-item">
                <div class="info-label">Total Hectáreas:</div>
                <div class="info-value" style="color: #007bff; font-weight: bold;">{{ number_format($totalHectareas, 2, ',', '.') }} ha</div>
            </div>
            <div class="info-item">
                <div class="info-label">Recomendado por:</div>
                <div class="info-value">{{ $order->recomendado }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Responsable:</div>
                <div class="info-value">{{ $order->responsable }}</div>
            </div>
        </div>
        <div style="margin-top: 10px;">
            <div class="info-label">Aplicadores:</div>
            <div class="info-value">{{ $order->aplicadores }}</div>
        </div>
        @if($order->observations)
        <div style="margin-top: 10px;">
            <div class="info-label">Observaciones:</div>
            <div class="info-value">{{ $order->observations }}</div>
        </div>
        @endif
    </div>

    <!-- Centros de Costo -->
    <div class="info-section" style="border-left-color: #28a745;">
        <h2>🗺️ Centros de Costo</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th>Centro de Costo</th>
                    <th class="text-right" style="width: 25%;">Superficie (ha)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->orderCostCenters as $index => $occ)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $occ->costCenter->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($occ->costCenter->surface ?? 0, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #999;">No hay centros de costo asociados</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-highlight">
                    <td colspan="2" class="text-right">TOTAL:</td>
                    <td class="text-right">{{ number_format($totalHectareas, 2, ',', '.') }} ha</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Productos a Aplicar -->
    <div class="info-section" style="border-left-color: #17a2b8;">
        <h2>🧪 Productos a Aplicar</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 20%;">Producto</th>
                    <th style="width: 12%;">Tipo Dosis</th>
                    <th class="text-right" style="width: 15%;">Dosis</th>
                    <th class="text-right" style="width: 13%;">Cant./ha</th>
                    <th class="text-right" style="width: 15%;">Cant. Total</th>
                    <th class="text-center" style="width: 10%;">Carencia</th>
                    <th class="text-center" style="width: 10%;">Reingreso</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->orderProducts as $index => $op)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $op->product->name ?? 'N/A' }}</strong></td>
                    <td>
                        @if($op->tipo_dosis === 'por_hectarea')
                            <span class="badge-primary">Por Hectárea</span>
                        @else
                            <span class="badge-info">Por 100L</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($op->tipo_dosis === 'por_hectarea')
                            {{ number_format($op->dosis_por_hectarea, 2, ',', '.') }} {{ $op->product->unit->name ?? '' }}/ha
                        @else
                            {{ number_format($op->dosis_por_100, 2, ',', '.') }} {{ $op->product->unit->name ?? '' }}/100L
                        @endif
                    </td>
                    <td class="text-right">
                        {{ number_format($op->cantidad_por_hectarea, 2, ',', '.') }} {{ $op->product->unit->name ?? '' }}/ha
                    </td>
                    <td class="text-right" style="font-weight: bold; color: #007bff;">
                        {{ number_format($op->cantidad_total, 2, ',', '.') }} {{ $op->product->unit->name ?? '' }}
                    </td>
                    <td class="text-center">
                        <span class="badge-warning">{{ $op->carencia }} días</span>
                    </td>
                    <td class="text-center">
                        <span class="badge-secondary">{{ $op->reingreso }} días</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center" style="color: #999;">No hay productos asociados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Firmas -->
    <div class="signature-section">
        <div class="signature-box">
            <div style="height: 40px;"></div>
            <div class="signature-label">{{ $order->recomendado }}</div>
            <div style="font-size: 9px; color: #999;">Recomendado por</div>
        </div>
        <div class="signature-box">
            <div style="height: 40px;"></div>
            <div class="signature-label">{{ $order->responsable }}</div>
            <div style="font-size: 9px; color: #999;">Responsable</div>
        </div>
        <div class="signature-box">
            <div style="height: 40px;"></div>
            <div class="signature-label">_________________</div>
            <div style="font-size: 9px; color: #999;">Aplicador</div>
        </div>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <p>Este documento es una orden de aplicación generada por el sistema de gestión presupuestaria.</p>
        <p>Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
