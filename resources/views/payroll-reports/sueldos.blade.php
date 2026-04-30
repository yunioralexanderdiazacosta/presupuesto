<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Sueldos — {{ $monthLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; padding: 20px 24px; }

        .page-header {
            background: #1a3c5e;
            color: white;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .page-header h1 { font-size: 13px; font-weight: bold; }
        .page-header p  { font-size: 8px; opacity: 0.8; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            background: #b8d0e0;
            color: #1a3c5e;
            padding: 5px 7px;
            text-align: left;
            font-size: 7.5px;
            font-weight: bold;
            border-bottom: 2px solid #1a3c5e;
        }
        th.right, td.right { text-align: right; }
        th.center, td.center { text-align: center; }

        td {
            padding: 4px 7px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 8px;
        }
        tr:nth-child(even) td { background: #f5f8fc; }

        tfoot td {
            background: #f0f4f8 !important;
            color: #1a3c5e !important;
            font-weight: bold;
            padding: 5px 7px;
            font-size: 8.5px;
            border-top: 2px solid #1a3c5e;
        }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
            font-size: 7.5px;
            color: #888;
            text-align: center;
        }

        .badge-contract {
            background: #dbeafe;
            color: #1e40af;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
        }
    </style>
</head>
<body>

    <div class="page-header">
        <h1>&#128196; Resumen de Sueldos &mdash; {{ $monthLabel }}</h1>
        <p>Generado el {{ now()->format('d/m/Y H:i') }} &bull; {{ count($rows) }} colaborador(es)</p>
    </div>

    @if(count($rows) === 0)
        <p style="text-align:center; color:#999; padding:20px;">No hay datos para este período.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>RUT</th>
                    <th class="center">Contrato</th>
                    <th>Nombre Completo</th>
                    <th class="right">Total Diario</th>
                    <th class="right">Bonos Diarios</th>
                    <th class="right">Bonos Mens.</th>
                    <th class="right">Monto HE</th>
                    <th class="right">Total Haberes</th>
                    <th class="right">Descuentos</th>
                    <th class="right">Total a Pago</th>
                    <th class="right">Jornadas</th>
                    <th class="right">Prom. JH</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td style="white-space:nowrap;">{{ $row['rut'] }}</td>
                    <td class="center">
                        @if($row['contract_id'] !== '—')
                            <span class="badge-contract">#{{ $row['contract_id'] }}</span>
                        @else
                            <span style="color:#999;">—</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap; font-weight:600;">{{ $row['full_name'] }}</td>
                    <td class="right">$ {{ number_format($row['total_diario'], 0, ',', '.') }}</td>
                    <td class="right">{{ $row['bonos_diarios'] > 0 ? '$ ' . number_format($row['bonos_diarios'], 0, ',', '.') : '—' }}</td>
                    <td class="right">{{ $row['bonus_mensual'] > 0 ? '$ ' . number_format($row['bonus_mensual'], 0, ',', '.') : '—' }}</td>
                    <td class="right">{{ $row['horas_extra'] > 0 ? '$ ' . number_format($row['horas_extra'], 0, ',', '.') : '—' }}</td>
                    <td class="right">$ {{ number_format($row['total_haberes'], 0, ',', '.') }}</td>
                    <td class="right">{{ $row['descuentos'] > 0 ? '- $ ' . number_format($row['descuentos'], 0, ',', '.') : '—' }}</td>
                    <td class="right" style="font-weight:bold;">$ {{ number_format($row['total_neto'], 0, ',', '.') }}</td>
                    <td class="right">{{ $row['workdays'] > 0 ? $row['workdays'] : '—' }}</td>
                    <td class="right">{{ $row['promedio_jh'] > 0 ? '$ ' . number_format($row['promedio_jh'], 0, ',', '.') : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOTALES</td>
                    <td class="right">$ {{ number_format($totals['total_diario'], 0, ',', '.') }}</td>
                    <td class="right">$ {{ number_format($totals['bonos_diarios'], 0, ',', '.') }}</td>
                    <td class="right">$ {{ number_format($totals['bonus_mensual'], 0, ',', '.') }}</td>
                    <td class="right">$ {{ number_format($totals['horas_extra'], 0, ',', '.') }}</td>
                    <td class="right">$ {{ number_format($totals['total_haberes'], 0, ',', '.') }}</td>
                    <td class="right">- $ {{ number_format($totals['descuentos'], 0, ',', '.') }}</td>
                    <td class="right">$ {{ number_format($totals['total_neto'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($totals['workdays'], 2, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        Resumen de Sueldos &bull; {{ $monthLabel }} &bull; Sistema de Gestión Agrícola
    </div>

</body>
</html>
