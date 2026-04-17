<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla Tarjas - {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 7px; line-height: 1.2; color: #333; padding: 8px; }
        .header { text-align: center; margin-bottom: 8px; border-bottom: 2px solid #2c7be5; padding-bottom: 5px; }
        .header h1 { font-size: 14px; color: #2c7be5; margin-bottom: 2px; }
        .header p { font-size: 9px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ccc; padding: 2px 3px; text-align: center; }
        th { background-color: #2c7be5; color: #fff; font-size: 7px; }
        td { font-size: 7px; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .total-row { background-color: #e8f0fe; font-weight: bold; }
        .day-header { width: 22px; min-width: 22px; }
        .name-col { min-width: 100px; text-align: left; white-space: nowrap; }
        .footer { margin-top: 10px; font-size: 8px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Planilla de Tarjas</h1>
        <p>{{ $team->name ?? '' }} â€” {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="name-col">Colaborador</th>
                @foreach($dates as $date)
                    <th class="day-header">{{ \Carbon\Carbon::parse($date)->format('d') }}</th>
                @endforeach
                <th>Total $</th>
                <th>Bono $</th>
                <th>JH</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
            <tr>
                <td class="name-col">{{ $emp['full_name'] }}</td>
                @foreach($dates as $date)
                    <td>{{ $emp['days'][$date]['amount'] ? number_format($emp['days'][$date]['amount'], 0, ',', '.') : '' }}</td>
                @endforeach
                <td class="fw-bold text-right">{{ number_format($emp['grand_total_amount'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($emp['grand_total_bonus'], 0, ',', '.') }}</td>
                <td>{{ $emp['grand_total_workdays'] }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td class="text-left fw-bold">TOTAL GENERAL</td>
                @foreach($dates as $date)
                    @php $dayTotal = collect($employees)->sum(fn($e) => $e['days'][$date]['amount']); @endphp
                    <td>{{ $dayTotal ? number_format($dayTotal, 0, ',', '.') : '' }}</td>
                @endforeach
                <td class="text-right">{{ number_format(collect($employees)->sum('grand_total_amount'), 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format(collect($employees)->sum('grand_total_bonus'), 0, ',', '.') }}</td>
                <td>{{ collect($employees)->sum('grand_total_workdays') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
