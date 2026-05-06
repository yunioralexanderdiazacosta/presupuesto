<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Aplicación #{{ $order->id }}</title>
    
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #333;
            padding: 10px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 6px;
        }
        
        .header h1 {
            font-size: 16px;
            color: #007bff;
            margin-bottom: 3px;
        }
        
        .header p {
            font-size: 10px;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 6px;
            padding: 5px;
            background-color: #f8f9fa;
            border-left: 3px solid #007bff;
        }
        
        .info-section h2 {
            font-size: 12px;
            color: #007bff;
            margin-bottom: 4px;
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
            font-size: 9px;
            text-transform: uppercase;
        }
        
        .info-value {
            color: #333;
            font-size: 10px;
            margin-top: 1px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 9px;
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
            margin-top: 3px;
            margin-bottom: 5px;
        }
        
        table th {
            background-color: #007bff;
            color: white;
            padding: 4px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        
        table td {
            padding: 3px 4px;
            border-bottom: 1px solid #dee2e6;
            font-size: 9px;
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
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 8.5px;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 8.5px;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #000;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 8.5px;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 8.5px;
        }
        
        .footer {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        
        .signature-section {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .signature-box {
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 5px;
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
            padding: 4px;
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
        <p style="font-size: 11px; color: #999;">Orden #{{ $order->id }} - Fecha de emisión: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Información General -->
    <div class="info-section">
        <h2>:: Información General</h2>
        <table style="border: none; margin: 0;">
            <tr>
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Fecha:</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</div>
                </td>
                @if($order->start_date)
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Inicio:</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($order->start_date)->format('d/m/Y') }}</div>
                </td>
                @endif
                @if($order->volume)
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Volumen:</div>
                    <div class="info-value">{{ number_format($order->volume, 0, ',', '.') }} L</div>
                </td>
                @endif
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Mojamiento:</div>
                    <div class="info-value">{{ number_format($order->mojamiento, 0, ',', '.') }} L</div>
                </td>
                @if($order->volume)
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Maquinadas:</div>
                    <div class="info-value" style="color: #007bff; font-weight: bold;">{{ number_format(($order->mojamiento * $totalHectareas) / $order->volume, 1, ',', '.') }}</div>
                </td>
                @endif
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Total ha:</div>
                    <div class="info-value" style="color: #28a745; font-weight: bold;">{{ number_format($totalHectareas, 2, ',', '.') }} ha</div>
                </td>
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    @php
                        $statusLabels = [
                            'pendiente' => 'Pendiente',
                            'en_proceso' => 'En Proceso',
                            'completada' => 'Completada',
                            'cancelada' => 'Cancelada'
                        ];
                        $statusClass = 'status-' . $order->status;
                    @endphp
                    <div class="info-label">Estado:</div>
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                </td>
            </tr>
            <tr>
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Recomendado por:</div>
                    <div class="info-value">{{ $order->recomendado }}</div>
                </td>
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Responsable:</div>
                    <div class="info-value">{{ $order->responsable }}</div>
                </td>
                @if($order->phenologicalStage)
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Fenología:</div>
                    <div class="info-value" style="color: #28a745; font-weight: bold;">{{ $order->phenologicalStage->name }}</div>
                </td>
                @endif
                @if($order->tractors)
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Tractores:</div>
                    <div class="info-value">{{ $order->tractors }}</div>
                </td>
                @endif
                @if($order->equipments)
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Equipos:</div>
                    <div class="info-value">{{ $order->equipments }}</div>
                </td>
                @endif
                @if($order->operators)
                <td style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Operarios:</div>
                    <div class="info-value">{{ $order->operators }}</div>
                </td>
                @endif
            </tr>
            @if($order->observations)
            <tr>
                <td colspan="7" style="border: none; padding: 2px 4px; vertical-align: top;">
                    <div class="info-label">Observaciones:</div>
                    <div class="info-value">{{ $order->observations }}</div>
                </td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Centros de Costo -->
    <div class="info-section" style="border-left-color: #28a745;">
        <h2>:: Centros de Costo</h2>
        @if($order->orderCostCenters->count() > 0)
        @php
            $ccItems = $order->orderCostCenters->values();
            $maxPerCol = 15;
            $ccChunks = $ccItems->chunk($maxPerCol);
            $numCols = $ccChunks->count();
            $colWidth = floor(100 / $numCols);
        @endphp
        <table style="border: none; margin: 0; width: 100%;">
            <tr>
                @foreach($ccChunks as $chunkIdx => $chunk)
                <td style="width: {{ $colWidth }}%; border: none; padding: 0 {{ $chunkIdx > 0 ? '0 0 4px' : '0' }}; vertical-align: top;">
                    <table style="width: 100%; margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 25px;">#</th>
                                <th>Centro de Costo</th>
                                <th class="text-right" style="width: 60px;">Ha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chunk as $occ)
                            <tr>
                                <td>{{ $loop->parent->index * $maxPerCol + $loop->iteration }}</td>
                                <td>{{ $occ->costCenter->name ?? 'N/A' }}</td>
                                <td class="text-right">{{ number_format($occ->costCenter->surface ?? 0, 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                @endforeach
            </tr>
        </table>
        <table style="margin: 0; width: 100%;">
            <tr class="total-highlight">
                <td class="text-right" style="font-weight: bold;">TOTAL: {{ number_format($totalHectareas, 2, ',', '.') }} ha</td>
            </tr>
        </table>
        @else
        <p style="color: #999; text-align: center;">No hay centros de costo asociados</p>
        @endif
    </div>

    <!-- Productos a Aplicar -->
    <div class="info-section" style="border-left-color: #17a2b8;">
        <h2>:: Productos a Aplicar</h2>
        <p style="font-size: 8.5px; color: #666; margin-bottom: 3px; font-style: italic;">
            * Cantidades mostradas en unidades prácticas para aplicación en campo (cc, gr, etc.)
        </p>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 16%;">Producto</th>
                    <th style="width: 14%;">Ing. Activo</th>
                    <th style="width: 10%;">Tipo Dosis</th>
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
                    <td style="color: #666;">{{ $op->product->active_ingredient ?? '-' }}</td>
                    <td>
                        @if($op->tipo_dosis === 'por_hectarea')
                            <span class="badge-primary">Por Hectárea</span>
                        @else
                            <span class="badge-info">Por 100L</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($op->tipo_dosis === 'por_hectarea')
                            @php
                                $converted = convertToPracticalUnit($op->dosis_por_hectarea, $op->product->unit->name ?? '');
                            @endphp
                            {{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}/ha
                        @else
                            @php
                                $converted = convertToPracticalUnit($op->dosis_por_100, $op->product->unit->name ?? '');
                            @endphp
                            {{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}/100L
                        @endif
                    </td>
                    <td class="text-right">
                        @php
                            $converted = convertToPracticalUnit($op->cantidad_por_hectarea, $op->product->unit->name ?? '');
                        @endphp
                        {{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}/ha
                    </td>
                    <td class="text-right" style="font-weight: bold; color: #007bff;">
                        @php
                            $converted = convertToPracticalUnit($op->cantidad_total, $op->product->unit->name ?? '');
                        @endphp
                        {{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}
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
                    <td colspan="9" class="text-center" style="color: #999;">No hay productos asociados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Receta por Maquinada -->
    @if($order->volume && $order->volume > 0 && $order->orderProducts->count() > 0)
    @php
        $maquinadas = ($order->mojamiento * $totalHectareas) / $order->volume;
        $maquinadasCompletas = floor($maquinadas);
        $fraccionSaldo = round($maquinadas - $maquinadasCompletas, 2);
        $aguaPorMaquinada = $order->volume;
        $aguaSaldo = round($aguaPorMaquinada * $fraccionSaldo);
    @endphp
    <div class="info-section" style="border-left-color: #ffc107;">
        <h2>:: Receta por Maquinada</h2>
        <table style="border: none; margin: 0;">
            <tr>
                @if($maquinadasCompletas > 0)
                <td style="width: {{ $fraccionSaldo > 0 ? '50%' : '100%' }}; border: none; padding: 5px; vertical-align: top;">
                    <div style="border: 1px solid #007bff; padding: 5px;">
                        <div style="background-color: #007bff; color: white; padding: 3px 5px; font-weight: bold; font-size: 9px; margin: -5px -5px 5px -5px;">
                            MAQUINADAS COMPLETAS: {{ $maquinadasCompletas }}
                        </div>
                        <div style="margin-bottom: 4px; font-size: 9px;">
                            <strong>Agua: {{ number_format($aguaPorMaquinada, 0, ',', '.') }} L</strong>
                        </div>
                        <table style="margin: 0;">
                            <thead>
                                <tr>
                                    <th style="font-size: 8.5px;">Producto</th>
                                    <th class="text-right" style="font-size: 8.5px;">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderProducts as $op)
                                @php
                                    $cantPorMaq = ($maquinadas > 0) ? $op->cantidad_total / $maquinadas : 0;
                                    $converted = convertToPracticalUnit($cantPorMaq, $op->product->unit->name ?? '');
                                @endphp
                                <tr>
                                    <td style="font-size: 8px;">{{ $op->product->name ?? 'N/A' }}</td>
                                    <td class="text-right" style="font-size: 8px; font-weight: bold;">{{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </td>
                @endif

                @if($fraccionSaldo > 0)
                <td style="width: {{ $maquinadasCompletas > 0 ? '50%' : '100%' }}; border: none; padding: 5px; vertical-align: top;">
                    <div style="border: 1px solid #ffc107; padding: 5px;">
                        <div style="background-color: #ffc107; color: #000; padding: 3px 5px; font-weight: bold; font-size: 9px; margin: -5px -5px 5px -5px;">
                            MAQUINADA DE SALDO ({{ number_format($fraccionSaldo, 2, ',', '.') }})
                        </div>
                        <div style="margin-bottom: 4px; font-size: 9px;">
                            <strong>Agua: {{ number_format($aguaSaldo, 0, ',', '.') }} L</strong>
                        </div>
                        <table style="margin: 0;">
                            <thead>
                                <tr>
                                    <th style="font-size: 8.5px;">Producto</th>
                                    <th class="text-right" style="font-size: 8.5px;">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderProducts as $op)
                                @php
                                    $cantPorMaq = ($maquinadas > 0) ? $op->cantidad_total / $maquinadas : 0;
                                    $cantSaldo = $cantPorMaq * $fraccionSaldo;
                                    $converted = convertToPracticalUnit($cantSaldo, $op->product->unit->name ?? '');
                                @endphp
                                <tr>
                                    <td style="font-size: 8px;">{{ $op->product->name ?? 'N/A' }}</td>
                                    <td class="text-right" style="font-size: 8px; font-weight: bold;">{{ formatQuantityForPdf($converted['value']) }} {{ $converted['unit'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </td>
                @endif
            </tr>
        </table>
    </div>
    @endif

    <!-- Condiciones Climáticas -->
    <div class="info-section" style="border-left-color: #6c757d; margin-top: 6px; page-break-inside: avoid;">
        <h2 style="color: #555; margin-bottom: 4px;">:: Condiciones Climáticas</h2>
        <table style="border: none; margin: 0; width: 100%; border-collapse: collapse;">
            <tr>
                <!-- VIENTO -->
                <td style="border: none; vertical-align: top; padding-right: 8px; width: 40%; border-right: 1px solid #ddd;">
                    <div style="font-size: 9px; font-weight: bold; color: #0055aa; text-transform: uppercase; margin-bottom: 3px;">Viento</div>
                    @php
                        $windLevels = [
                            ['label' => 'Calma',    'range' => '0-0,5',  'points' => '5,3 9,3 8,14'],
                            ['label' => 'Leve',     'range' => '0,5-2',  'points' => '4.5,3.5 8.5,2 14,11'],
                            ['label' => 'Moderado', 'range' => '2-3',    'points' => '4,4.5 8,1 17,9'],
                            ['label' => 'Fuerte',   'range' => '3-5',    'points' => '4.5,6 7.5,1 20,7'],
                            ['label' => 'M. fuerte','range' => '> 5',    'points' => '5,7 7,2 22,5'],
                        ];
                    @endphp
                    <table style="border: none; margin: 0; width: 100%;">
                        <tr>
                            @foreach($windLevels as $wind)
                            <td style="border: none; text-align: center; vertical-align: bottom; padding: 0 2px;">
                                <svg width="26" height="20" viewBox="0 0 26 20">
                                    <circle cx="7" cy="19" r="2" fill="#555"/>
                                    <line x1="7" y1="19" x2="7" y2="3" stroke="#555" stroke-width="1.5"/>
                                    <polygon points="{{ $wind['points'] }}" fill="#3a7bd5" stroke="#1a5bc5" stroke-width="0.5"/>
                                </svg>
                                <div style="width: 10px; height: 10px; border: 1.5px solid #333; background: white; margin: 2px auto;"></div>
                                <div style="font-size: 7.5px; color: #333; font-weight: bold; line-height: 1.1;">{{ $wind['label'] }}</div>
                                <div style="font-size: 7px; color: #888;">{{ $wind['range'] }}</div>
                            </td>
                            @endforeach
                        </tr>
                    </table>
                </td>

                <!-- TEMPERATURA -->
                <td style="border: none; vertical-align: top; padding: 0 8px; width: 32%; border-right: 1px solid #ddd;">
                    <div style="font-size: 9px; font-weight: bold; color: #cc2200; text-transform: uppercase; margin-bottom: 3px;">Temperatura (°C)</div>
                    @php
                        $tempRanges = [
                            ['label' => '< 5°',     'color' => '#4a90e2'],
                            ['label' => '5-10°',    'color' => '#74c0e0'],
                            ['label' => '10-15°',   'color' => '#a8d5a2'],
                            ['label' => '15-20°',   'color' => '#c8e86c'],
                            ['label' => '20-25°',   'color' => '#ffd04a'],
                            ['label' => '25-30°',   'color' => '#ff9020'],
                            ['label' => '> 30°',    'color' => '#e63900'],
                        ];
                    @endphp
                    <table style="border: none; margin: 0; width: 100%;">
                        <tr>
                            @foreach($tempRanges as $temp)
                            <td style="border: none; text-align: center; padding: 0 1px;">
                                <div style="width: 13px; height: 13px; border-radius: 50%; background: {{ $temp['color'] }}; margin: 0 auto; border: 0.5px solid rgba(0,0,0,0.2);"></div>
                                <div style="width: 10px; height: 10px; border: 1.5px solid #333; background: white; margin: 2px auto;"></div>
                                <div style="font-size: 7px; color: #444; white-space: nowrap;">{{ $temp['label'] }}</div>
                            </td>
                            @endforeach
                        </tr>
                    </table>
                </td>

                <!-- CONDICIÓN CLIMÁTICA -->
                <td style="border: none; vertical-align: top; padding-left: 8px; width: 28%;">
                    <div style="font-size: 9px; font-weight: bold; color: #cc7700; text-transform: uppercase; margin-bottom: 3px;">Condición</div>
                    <table style="border: none; margin: 0; width: 100%;">
                        <tr>
                            <td style="border: none; text-align: center; padding: 0 4px;">
                                <svg width="22" height="22" viewBox="0 0 30 30">
                                    <circle cx="15" cy="15" r="7" fill="#FFD700"/>
                                    <line x1="15" y1="1" x2="15" y2="5"   stroke="#FFD700" stroke-width="2"/>
                                    <line x1="15" y1="25" x2="15" y2="29" stroke="#FFD700" stroke-width="2"/>
                                    <line x1="1"  y1="15" x2="5"  y2="15" stroke="#FFD700" stroke-width="2"/>
                                    <line x1="25" y1="15" x2="29" y2="15" stroke="#FFD700" stroke-width="2"/>
                                    <line x1="4.5" y1="4.5" x2="7.3" y2="7.3" stroke="#FFD700" stroke-width="2"/>
                                    <line x1="22.7" y1="22.7" x2="25.5" y2="25.5" stroke="#FFD700" stroke-width="2"/>
                                    <line x1="25.5" y1="4.5" x2="22.7" y2="7.3" stroke="#FFD700" stroke-width="2"/>
                                    <line x1="7.3" y1="22.7" x2="4.5" y2="25.5" stroke="#FFD700" stroke-width="2"/>
                                </svg>
                                <div style="width: 10px; height: 10px; border: 1.5px solid #333; background: white; margin: 2px auto;"></div>
                                <div style="font-size: 7.5px; color: #444;">Soleado</div>
                            </td>
                            <td style="border: none; text-align: center; padding: 0 4px;">
                                <svg width="28" height="22" viewBox="0 0 38 28">
                                    <circle cx="10" cy="12" r="7" fill="#FFD700"/>
                                    <circle cx="15" cy="20" r="6" fill="#ccc"/>
                                    <circle cx="24" cy="17" r="8" fill="#ddd"/>
                                    <circle cx="32" cy="20" r="5" fill="#ccc"/>
                                    <rect x="9" y="20" width="25" height="6" fill="#ddd"/>
                                </svg>
                                <div style="width: 10px; height: 10px; border: 1.5px solid #333; background: white; margin: 2px auto;"></div>
                                <div style="font-size: 7.5px; color: #444;">Parcial</div>
                            </td>
                            <td style="border: none; text-align: center; padding: 0 4px;">
                                <svg width="28" height="20" viewBox="0 0 38 24">
                                    <circle cx="10" cy="13" r="8" fill="#aaa"/>
                                    <circle cx="21" cy="10" r="9" fill="#bbb"/>
                                    <circle cx="31" cy="13" r="7" fill="#aaa"/>
                                    <rect x="2" y="13" width="34" height="7" fill="#aaa"/>
                                </svg>
                                <div style="width: 10px; height: 10px; border: 1.5px solid #333; background: white; margin: 2px auto;"></div>
                                <div style="font-size: 7.5px; color: #444;">Nublado</div>
                            </td>
                            <td style="border: none; text-align: center; padding: 0 4px;">
                                <svg width="28" height="22" viewBox="0 0 38 28">
                                    <circle cx="10" cy="10" r="7" fill="#888"/>
                                    <circle cx="20" cy="8"  r="8" fill="#999"/>
                                    <circle cx="30" cy="11" r="6" fill="#888"/>
                                    <rect x="3" y="11" width="30" height="4" fill="#888"/>
                                    <line x1="10" y1="19" x2="7"  y2="27" stroke="#4a90d9" stroke-width="1.5" stroke-linecap="round"/>
                                    <line x1="19" y1="19" x2="16" y2="27" stroke="#4a90d9" stroke-width="1.5" stroke-linecap="round"/>
                                    <line x1="28" y1="19" x2="25" y2="27" stroke="#4a90d9" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                <div style="width: 10px; height: 10px; border: 1.5px solid #333; background: white; margin: 2px auto;"></div>
                                <div style="font-size: 7.5px; color: #444;">Lluvia</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <div style="margin-top: 4px; padding: 2px 4px; border: 1px dashed #ccc; background: #fafafa;">
            <span style="font-size: 8.5px; color: #888; font-weight: bold;">Obs. climáticas:</span>
            <div style="height: 10px;"></div>
        </div>

        <!-- Elementos de Protección -->
        <div style="margin-top: 5px; border: 1px solid #ccc; padding: 4px 6px;">
            <div style="font-size: 9px; font-weight: bold; color: #333; text-transform: uppercase; margin-bottom: 4px;">
                Elemento de protección
            </div>
            <table style="border: none; margin: 0; width: 100%;">
                <tr>
                    @php
                        $epps = ['Guantes', 'Mascarilla', 'Traje impermeable', 'Botas de agua', 'Otros'];
                    @endphp
                    @foreach($epps as $epp)
                    <td style="border: none; white-space: nowrap; padding: 0 6px 0 0;">
                        <span style="font-size: 9px; color: #333;">{{ $epp }}</span>
                        <span style="display: inline-block; width: 11px; height: 11px; border: 1.5px solid #333; background: white; vertical-align: middle; margin-left: 2px;"></span>
                    </td>
                    @endforeach
                </tr>
            </table>
        </div>
    </div>

    <!-- Firmas -->
    <div class="signature-section">
        <div class="signature-box">
            <div style="height: 20px;"></div>
            <div class="signature-label">{{ $order->responsable }}</div>
            <div style="font-size: 8.5px; color: #999;">Responsable</div>
        </div>
        <div class="signature-box">
            <div style="height: 20px;"></div>
            <div class="signature-label">_________________</div>
            <div style="font-size: 8.5px; color: #999;">Aplicador</div>
        </div>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <p>Este documento es una orden de aplicación generada por el sistema de gestión agrícola Alisoft.</p>
        <p>Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
