<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Fertilizante #{{ $order->id }}</title>
    
    @php
    /**
     * Convierte cantidades a unidades prácticas para aplicación en campo
     * Si cantidad < 1 lt -> muestra en cc
     * Si cantidad < 1 kg -> muestra en gr
     */
    function convertToPracticalUnit($quantity, $unitName) {
        $unitNameLower = strtolower($unitName);
        
        // Convertir lt a cc si es menor a 1
        if ($unitNameLower === 'lt' && $quantity < 1) {
            return [
                'value' => $quantity * 1000,
                'unit' => 'cc'
            ];
        }
        
        // Convertir kg a gr si es menor a 1
        if ($unitNameLower === 'kg' && $quantity < 1) {
            return [
                'value' => $quantity * 1000,
                'unit' => 'gr'
            ];
        }
        
        // No convertir, devolver original
        return [
            'value' => $quantity,
            'unit' => $unitName
        ];
    }
    
    /**
     * Formatea el número para mostrar en PDF (sin decimales innecesarios)
     */
    function formatQuantityForPdf($value) {
        // Si el valor es entero o tiene solo ceros decimales, mostrar sin decimales
        if (floor($value) == $value) {
            return number_format($value, 0, ',', '.');
        }
        // Si tiene decimales significativos, mostrar 2 decimales
        return number_format($value, 2, ',', '.');
    }
    @endphp
    
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
            color: #333;
            padding: 10px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #7fb89e;
            padding-bottom: 6px;
        }
        
        .header h1 {
            font-size: 16px;
            color: #7fb89e;
            margin-bottom: 3px;
        }
        
        .header p {
            font-size: 8px;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 6px;
            padding: 5px;
            background-color: #f8f9fa;
            border-left: 3px solid #7fb89e;
        }
        
        .info-section h2 {
            font-size: 10px;
            color: #6a9c85;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 2px 8px 2px 0;
            color: #555;
            width: 25%;
        }
        
        .info-value {
            display: table-cell;
            padding: 2px 0;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }
        
        th {
            background-color: #a8d5ba;
            color: #2d5f4a;
            padding: 4px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #8fc4a6;
        }
        
        td {
            padding: 3px 4px;
            border: 1px solid #ddd;
            font-size: 8px;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
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
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            display: inline-block;
        }
        
        .badge-success {
            background-color: #a8d5ba;
            color: #2d5f4a;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            display: inline-block;
        }
        
        .badge-warning {
            background-color: #f5d6a3;
            color: #6b5438;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            display: inline-block;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            display: inline-block;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            display: inline-block;
        }
        
        .total-highlight {
            background-color: #e8f5ed !important;
            font-weight: bold;
        }
        
        .signature-section {
            margin-top: 20px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            text-align: center;
            padding: 10px;
            width: 50%;
        }
        
        .signature-label {
            border-top: 1px solid #333;
            padding-top: 3px;
            margin-top: 25px;
            font-weight: bold;
            font-size: 9px;
        }
        
        .footer {
            position: fixed;
            bottom: 5px;
            left: 10px;
            right: 10px;
            text-align: center;
            font-size: 7px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 3px;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>ORDEN DE FERTILIZANTE #{{ $order->id }}</h1>
        <p>{{ $team->name ?? 'Equipo' }} - Temporada {{ $order->season->name ?? 'N/A' }}</p>
        <p style="font-size: 10px; color: #999;">Orden #{{ $order->id }} - Fecha de emisión: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Información General -->
    <div class="info-section">
        <h2>:: Información General</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Fecha:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</div>
                <div class="info-label">Estado:</div>
                <div class="info-value">
                    @if($order->status === 'pending')
                        <span class="badge-warning">Pendiente</span>
                    @elseif($order->status === 'executed')
                        <span class="badge-success">Ejecutado</span>
                    @else
                        <span class="badge-secondary">{{ ucfirst($order->status) }}</span>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Bomba de Riego:</div>
                <div class="info-value">{{ $order->irrigationPump->name ?? 'N/A' }}</div>
                <div class="info-label">Responsable:</div>
                <div class="info-value">{{ $order->responsable ?? 'N/A' }}</div>
            </div>
            @if($order->observations)
            <div class="info-row">
                <div class="info-label">Observaciones:</div>
                <div class="info-value" style="width: 75%;">{{ $order->observations }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Resumen de Productos -->
    <div class="info-section" style="border-left-color: #8ab6c8;">
        <h2>:: Resumen de Fertilizantes</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Producto</th>
                    <th class="text-right" style="width: 20%;">Dosis/ha</th>
                    <th class="text-right" style="width: 20%;">Cant. Total</th>
                    <th style="width: 15%;">Unidad</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->orderProducts as $index => $op)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $op->product->name ?? 'N/A' }}</strong></td>
                    <td class="text-right">
                        @php
                            $unitName = $op->unit->name ?? $op->product->unit->name ?? '';
                            $converted = convertToPracticalUnit($op->dosis_por_hectarea, $unitName);
                        @endphp
                        {{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}/ha
                    </td>
                    <td class="text-right" style="font-weight: bold; color: #6a9c85;">
                        @php
                            $unitName = $op->unit->name ?? $op->product->unit->name ?? '';
                            $converted = convertToPracticalUnit($op->cantidad_total, $unitName);
                        @endphp
                        {{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}
                    </td>
                    <td>{{ $op->unit->name ?? $op->product->unit->name ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center" style="color: #999;">No hay productos asociados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Detalle por Sector de Riego - Matriz -->
    <div class="info-section" style="border-left-color: #92b4d4;">
        <h2>:: Cantidades por Sector de Riego</h2>
        <p style="font-size: 6px; color: #666; margin-bottom: 3px; font-style: italic;">
            * Detalle de cantidades a aplicar en cada sector
        </p>
        
        @php 
            $totalHectareas = 0;
            $sectorsCount = $order->orderIrrigationSectors->count();
        @endphp
        
        @if($sectorsCount > 0)
        <table style="margin-top: 0;">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Producto</th>
                    <th class="text-right" style="width: 12%;">Dosis/ha</th>
                    @foreach($order->orderIrrigationSectors as $ois)
                        @php $totalHectareas += $ois->surface; @endphp
                        <th class="text-center" style="width: {{ 53 / $sectorsCount }}%; background-color: #a3c8e7; border-color: #7ba6c9;">
                            <div style="font-size: 7px; line-height: 1.1;">
                                {{ $ois->irrigationSector->name ?? 'N/A' }}
                            </div>
                            <div style="font-size: 6px; color: #3d5f7a; font-weight: normal;">
                                ({{ number_format($ois->surface, 2, ',', '.') }} ha)
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($order->orderProducts as $prodIndex => $op)
                <tr>
                    <td>{{ $prodIndex + 1 }}</td>
                    <td><strong>{{ $op->product->name ?? 'N/A' }}</strong></td>
                    <td class="text-right">
                        @php
                            $unitName = $op->unit->name ?? $op->product->unit->name ?? '';
                            $converted = convertToPracticalUnit($op->dosis_por_hectarea, $unitName);
                        @endphp
                        {{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}/ha
                    </td>
                    @foreach($order->orderIrrigationSectors as $ois)
                        <td class="text-center" style="font-weight: bold; color: #5a8bb0;">
                            @php
                                $cantidadSector = $op->dosis_por_hectarea * $ois->surface;
                                $unitName = $op->unit->name ?? $op->product->unit->name ?? '';
                                $converted = convertToPracticalUnit($cantidadSector, $unitName);
                            @endphp
                            {{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}
                        </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 3 + $sectorsCount }}" class="text-center" style="color: #999;">No hay productos asociados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div style="margin-top: 5px; padding: 4px; background-color: #e8f5ed; text-align: right; font-weight: bold; color: #6a9c85;">
            SUPERFICIE TOTAL: {{ number_format($totalHectareas, 2, ',', '.') }} ha
        </div>
        @else
            <p style="text-align: center; color: #999; padding: 10px;">No hay sectores asociados</p>
        @endif
    </div>

    <!-- Centros de Costo -->
    <div class="info-section" style="border-left-color: #e8c97a;">
        <h2>:: Centros de Costo</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 90%;">Centro de Costo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->orderCostCenters as $index => $occ)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $occ->costCenter->name ?? 'N/A' }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" class="text-center" style="color: #999;">No hay centros de costo asociados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Firmas -->
    <div class="signature-section">
        <div class="signature-box">
            <div style="height: 20px;"></div>
            <div class="signature-label">{{ $order->responsable }}</div>
            <div style="font-size: 6px; color: #999;">Responsable</div>
        </div>
        <div class="signature-box">
            <div style="height: 20px;"></div>
            <div class="signature-label">_________________</div>
            <div style="font-size: 6px; color: #999;">Aplicador</div>
        </div>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <p>Este documento es una orden de fertilizante generada por el sistema de gestión presupuestaria.</p>
        <p>Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
