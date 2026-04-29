<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Horas Extras — {{ $monthLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }

        .page-header {
            background: #1a3c5e;
            color: white;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .page-header h1 { font-size: 13px; font-weight: bold; }
        .page-header p  { font-size: 8px; opacity: 0.8; margin-top: 2px; }

        .worker-block {
            margin-bottom: 14px;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            page-break-inside: avoid;
        }
        .worker-header {
            background: #e9ecef;
            padding: 5px 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .worker-name { font-size: 10px; font-weight: bold; color: #1a3c5e; }
        .worker-rut  { font-size: 8px; color: #666; margin-top: 1px; }

        table { width: 100%; border-collapse: collapse; }
        th {
            background: transparent;
            color: #333;
            padding: 4px 7px;
            text-align: left;
            font-size: 7.5px;
            font-weight: bold;
            border-bottom: 2px solid #ccc;
        }
        th.right, td.right { text-align: right; }
        td {
            padding: 3px 7px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 8.5px;
        }
        tr:nth-child(even) td { background: #f9f9f9; }

        tfoot td {
            background: transparent !important;
            color: #333;
            font-weight: bold;
            padding: 4px 7px;
            font-size: 8.5px;
            border-top: 2px solid #ccc;
        }

        .grand-total {
            margin-top: 16px;
            border: 2px solid #1a3c5e;
            padding: 8px 12px;
            display: table;
            width: 100%;
        }
        .grand-total-label { display: table-cell; font-size: 11px; font-weight: bold; color: #1a3c5e; }
        .grand-total-value { display: table-cell; text-align: right; font-size: 13px; font-weight: bold; color: #1a3c5e; }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
            font-size: 7.5px;
            color: #888;
            text-align: center;
        }

        .empty { color: #999; font-style: italic; font-size: 8px; padding: 10px; text-align: center; }
    </style>
</head>
<body>

    <div class="page-header">
        <h1><span style="opacity:0.7;">&#128336;</span> Reporte de Horas Extras &mdash; {{ $monthLabel }}</h1>
        <p>Generado el {{ $generatedAt }}</p>
    </div>

    @if($workers->isEmpty())
        <p class="empty">No hay horas extras registradas para los filtros seleccionados.</p>
    @else
        @foreach($workers as $worker)
            <div class="worker-block">
                <div class="worker-header">
                    <div class="worker-name">{{ $worker['name'] }}</div>
                    <div class="worker-rut">RUT: {{ $worker['rut'] }}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width:20%">Mes</th>
                            <th style="width:25%">Tipo HE</th>
                            <th style="width:30%">Labor</th>
                            <th style="width:12%" class="right">Horas</th>
                            <th style="width:13%" class="right">Costo Est.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($worker['lines'] as $line)
                            <tr>
                                <td>{{ $line['month'] }}</td>
                                <td>{{ $line['overtime_type'] }}</td>
                                <td>{{ $line['labor_type'] }}</td>
                                <td class="right">{{ number_format($line['hours'], 2, ',', '.') }}</td>
                                <td class="right">
                                    @if($line['amount'] > 0)
                                        $ {{ number_format($line['amount'], 0, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">TOTAL {{ strtoupper($worker['name']) }}</td>
                            <td class="right">{{ number_format($worker['total_hours'], 2, ',', '.') }} hrs</td>
                            <td class="right">
                                @if($worker['total_amount'] > 0)
                                    $ {{ number_format($worker['total_amount'], 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach

        {{-- Gran total --}}
        <div class="grand-total">
            <div class="grand-total-label">TOTAL GENERAL &mdash; {{ $monthLabel }}</div>
            <div class="grand-total-value">
                {{ number_format($totalHours, 2, ',', '.') }} hrs
                @if($totalAmount > 0)
                    &nbsp;&nbsp;|&nbsp;&nbsp;$ {{ number_format($totalAmount, 0, ',', '.') }}
                @endif
            </div>
        </div>
    @endif

    <div class="footer">
        Reporte Horas Extras &bull; {{ $monthLabel }} &bull; Generado el {{ $generatedAt }}
    </div>

</body>
</html>
