<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Tarjas - {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; line-height: 1.3; color: #333; padding: 10px; }
        .header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #2c7be5; padding-bottom: 6px; }
        .header h1 { font-size: 16px; color: #2c7be5; margin-bottom: 2px; }
        .header p { font-size: 10px; color: #666; }
        .employee-section { margin-bottom: 15px; page-break-after: always; }
        .employee-section:last-of-type { page-break-after: auto; }
        .employee-header { background-color: #2c7be5; color: #fff; padding: 4px 8px; font-size: 11px; font-weight: bold; margin-bottom: 4px; }
        .employee-info { font-size: 9px; color: #666; margin-bottom: 4px; padding-left: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #ddd; padding: 3px 5px; font-size: 8px; }
        th { background-color: #f0f4f8; font-weight: bold; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .subtotal-row { background-color: #e8f0fe; font-weight: bold; }
        .total-row { background-color: #2c7be5; color: #fff; font-weight: bold; font-size: 9px; }
        .badge { display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 7px; color: #fff; }
        .badge-trato { background-color: #2c7be5; }
        .badge-dia { background-color: #27bcfd; }
        .footer { margin-top: 10px; font-size: 8px; color: #999; text-align: center; }
    </style>
</head>
<body>

    @foreach($employees as $emp)
    <div class="employee-section">
        <div class="header">
            <h1>Detalle de Tarjas</h1>
            <p>{{ $team->name ?? '' }} — {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}</p>
        </div>
        <div class="employee-header">{{ $emp['full_name'] }}</div>
        <div class="employee-info">RUT: {{ $emp['rut'] }} | Cargo: {{ $emp['position'] ?: '-' }}</div>

        <table>
            <thead>
                <tr>
                    <th style="width:55px">Fecha</th>
                    <th style="width:40px">Tipo</th>
                    <th>Labor</th>
                    <th>Trato</th>
                    <th class="text-right" style="width:50px">Tarifa</th>
                    <th class="text-center" style="width:35px">Cant.</th>
                    <th class="text-right" style="width:60px">Monto</th>
                    <th class="text-center" style="width:35px">Hrs</th>
                    <th class="text-right" style="width:50px">Bono</th>
                    <th>C.Costo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dates as $date)
                    @php $dayData = $emp['days'][$date]; @endphp
                    @if($dayData['lines']->isNotEmpty())
                        @foreach($dayData['lines'] as $line)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</td>
                            <td class="text-center">
                                <span class="badge {{ ($line->payment_type ?? 'trato') === 'dia' ? 'badge-dia' : 'badge-trato' }}">
                                    {{ ($line->payment_type ?? 'trato') === 'dia' ? 'Día' : 'Trato' }}
                                </span>
                            </td>
                            <td>{{ $line->laborType?->name }}</td>
                            <td>{{ $line->laborRate?->name ?? '-' }}</td>
                            <td class="text-right">{{ number_format($line->rate, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $line->quantity }}</td>
                            <td class="text-right">{{ number_format($line->amount, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $line->hours }}</td>
                            <td class="text-right">{{ $line->bonus_amount ? number_format($line->bonus_amount, 0, ',', '.') : '' }}</td>
                            <td>{{ $line->costCenter?->name }}</td>
                        </tr>
                        @endforeach
                    @endif
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="6" style="text-align:right">TOTAL {{ $emp['full_name'] }}</td>
                    <td class="text-right">{{ number_format($emp['grand_total_amount'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ $emp['grand_total_hours'] }}</td>
                    <td class="text-right">{{ number_format($emp['grand_total_bonus'], 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach

    {{-- Resumen general --}}
    <table>
        <tr class="total-row">
            <td colspan="6" style="text-align:right">TOTAL GENERAL</td>
            <td class="text-right">{{ number_format(collect($employees)->sum('grand_total_amount'), 0, ',', '.') }}</td>
            <td class="text-center">{{ collect($employees)->sum('grand_total_hours') }}</td>
            <td class="text-right">{{ number_format(collect($employees)->sum('grand_total_bonus'), 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
