<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Facturas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            line-height: 1.3;
            color: #333;
            padding: 12px 14px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #1a56db;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 13px;
            color: #1a56db;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header p {
            font-size: 7.5px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr th {
            background-color: #1a56db;
            color: #fff;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 3px;
            border: 1px solid #1446b8;
            text-align: center;
        }

        tbody tr td {
            padding: 3px 3px;
            border: 1px solid #dde1e7;
            font-size: 7.5px;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) td {
            background-color: #f4f6fb;
        }

        tfoot tr td {
            padding: 4px 3px;
            border: 1px solid #b0bfdb;
            font-size: 8px;
            font-weight: bold;
            background-color: #e8eef8;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-muted  { color: #888; }

        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5px;
            font-weight: bold;
        }
        .badge-factura   { background: #dcfce7; color: #166534; }
        .badge-nc        { background: #fef3c7; color: #92400e; }
        .badge-nd        { background: #dbeafe; color: #1e40af; }
        .badge-boleta    { background: #f3e8ff; color: #6b21a8; }
        .badge-otro      { background: #f1f5f9; color: #475569; }

        .footer {
            text-align: center;
            font-size: 7px;
            color: #aaa;
            margin-top: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }

        .summary-box {
            display: inline-block;
            margin-bottom: 8px;
            border: 1px solid #c7d7f0;
            border-radius: 4px;
            padding: 4px 10px;
            background: #f0f5ff;
            font-size: 8px;
        }
        .summary-box span { margin-right: 16px; }
        .summary-box b   { color: #1a56db; }
    </style>
</head>
<body>

    <div class="header">
        <h1>REGISTRO DE FACTURAS Y DOCUMENTOS</h1>
        <p>Generado: {{ now()->format('d/m/Y H:i') }}@if($term) &nbsp;·&nbsp; Filtro: "{{ $term }}"@endif &nbsp;·&nbsp; {{ $invoices->count() }} documento(s)</p>
    </div>

    <div class="summary-box">
        <span>Neto total: <b>$ {{ number_format($totales['neto'], 0, ',', '.') }}</b></span>
        <span>IVA total: <b>$ {{ number_format($totales['iva'], 0, ',', '.') }}</b></span>
        <span>Total general: <b>$ {{ number_format($totales['total'], 0, ',', '.') }}</b></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo Doc.</th>
                <th>Mes</th>
                <th>Proveedor</th>
                <th>Razón Social</th>
                <th>N° Doc.</th>
                <th>Fecha</th>
                <th>Vencimiento</th>
                <th class="text-right">Neto</th>
                <th class="text-right">IVA (19%)</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
            @php
                $tipo = strtolower($inv['type_document']);
                $badgeClass = match(true) {
                    str_contains($tipo, 'factura')       => 'badge-factura',
                    str_contains($tipo, 'credito')       => 'badge-nc',
                    str_contains($tipo, 'debito')        => 'badge-nd',
                    str_contains($tipo, 'boleta')        => 'badge-boleta',
                    default                              => 'badge-otro',
                };
            @endphp
            <tr>
                <td class="text-center">{{ $inv['id'] }}</td>
                <td class="text-center"><span class="badge {{ $badgeClass }}">{{ $inv['type_document'] }}</span></td>
                <td class="text-center">{{ $inv['month'] }}</td>
                <td>{{ $inv['supplier'] }}</td>
                <td>{{ $inv['company_reason'] }}</td>
                <td class="text-center">{{ $inv['number_document'] }}</td>
                <td class="text-center">{{ $inv['date'] ? \Carbon\Carbon::parse($inv['date'])->format('d/m/Y') : '—' }}</td>
                <td class="text-center">{{ $inv['due_date'] ? \Carbon\Carbon::parse($inv['due_date'])->format('d/m/Y') : '—' }}</td>
                <td class="text-right">$ {{ number_format($inv['neto'], 0, ',', '.') }}</td>
                <td class="text-right">
                    @if($inv['iva'] > 0)
                        $ {{ number_format($inv['iva'], 0, ',', '.') }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-right"><b>$ {{ number_format($inv['total'], 0, ',', '.') }}</b></td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center text-muted" style="padding: 10px;">Sin registros</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="text-right">TOTALES</td>
                <td class="text-right">$ {{ number_format($totales['neto'], 0, ',', '.') }}</td>
                <td class="text-right">$ {{ number_format($totales['iva'], 0, ',', '.') }}</td>
                <td class="text-right">$ {{ number_format($totales['total'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">Sistema de Gestión Presupuestaria · {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>