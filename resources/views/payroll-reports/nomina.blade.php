<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nómina de Pago — {{ $monthLabel }}</title>
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

        th {
            background: transparent;
            color: #333;
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
            font-size: 8.5px;
        }
        tr:nth-child(even) td { background: #f5f8fc; }

        tfoot td {
            background: transparent !important;
            color: #333 !important;
            font-weight: bold;
            padding: 5px 7px;
            font-size: 9px;
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

        .badge-contract {
            background: #dbeafe;
            color: #1e40af;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
        }

        .mono { font-family: DejaVu Sans Mono, monospace; }
    </style>
</head>
<body>

    <div class="page-header">
        <h1><span style="opacity:0.7;">&#128196;</span> Nómina de Pago &mdash; {{ $monthLabel }}</h1>
        <p>Generado el {{ now()->format('d/m/Y H:i') }} &bull; {{ count($rows) }} colaborador(es)</p>
    </div>

    @if(count($rows) === 0)
        <p style="text-align:center; color:#999; padding:20px;">No hay datos de nómina para este período.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th class="center">#</th>
                    <th class="center">Contrato</th>
                    <th>RUT</th>
                    <th>Nombre Completo</th>
                    <th>Banco</th>
                    <th>Tipo Cuenta</th>
                    <th>N° Cuenta</th>
                    <th>Método Pago</th>
                    <th class="right">Total a Pagar</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr>
                    <td class="center" style="color:#999;">{{ $i + 1 }}</td>
                    <td class="center">
                        @if($row['contract_id'] !== '—')
                            <span class="badge-contract">#{{ $row['contract_id'] }}</span>
                        @else
                            <span style="color:#999;">—</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">{{ $row['rut'] }}</td>
                    <td style="white-space:nowrap; font-weight:600;">{{ $row['full_name'] }}</td>
                    <td>{{ $row['bank_name'] }}</td>
                    <td>{{ $row['account_type_name'] }}</td>
                    <td class="mono">{{ $row['account_number'] }}</td>
                    <td>{{ $row['payment_method_name'] }}</td>
                    <td class="right" style="font-weight:bold; color:#1a3c5e;">
                        $ {{ number_format($row['total_neto'], 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8">TOTAL A PAGAR</td>
                    <td class="right">$ {{ number_format($grandTotal, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="grand-total" style="margin-top:20px;">
            <div class="grand-total-label">Total Nómina {{ $monthLabel }}</div>
            <div class="grand-total-value">$ {{ number_format($grandTotal, 0, ',', '.') }}</div>
        </div>
    @endif

    <div class="footer">
        Nómina de Pago &bull; {{ $monthLabel }} &bull; Documento generado automáticamente
    </div>

</body>
</html>
