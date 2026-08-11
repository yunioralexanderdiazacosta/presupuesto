<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendición {{ $report->number }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            line-height: 1.3;
            color: #333;
            padding: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #2c7be5;
            padding-bottom: 6px;
        }

        .header h1 {
            font-size: 15px;
            color: #2c7be5;
            margin-bottom: 3px;
        }

        .header p {
            font-size: 8px;
            color: #666;
        }

        .info-section {
            margin-bottom: 8px;
            padding: 8px;
            background-color: #f8f9fa;
            border-left: 3px solid #2c7be5;
        }

        .info-grid {
            width: 100%;
            display: table;
        }

        .info-row {
            display: table-row;
        }

        .info-col {
            display: table-cell;
            width: 25%;
            padding: 3px 6px 3px 0;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 6px;
            text-transform: uppercase;
            display: block;
        }

        .info-value {
            color: #333;
            font-size: 8px;
            margin-top: 1px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7px;
            color: #fff;
        }

        .status-borrador { background-color: #6c757d; }
        .status-enviada { background-color: #17a2b8; }
        .status-aprobada { background-color: #2c7be5; }
        .status-pagada { background-color: #00d27a; }
        .status-rechazada { background-color: #e63757; }

        .rejection-box {
            margin-top: 6px;
            padding: 6px 8px;
            background-color: #fdecee;
            border-left: 3px solid #e63757;
            font-size: 7px;
            color: #7a1f2b;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 8px;
        }

        table.items th {
            background-color: #2c7be5;
            color: white;
            font-size: 6px;
            text-transform: uppercase;
            padding: 5px 4px;
            text-align: left;
        }

        table.items td {
            padding: 4px;
            border-bottom: 1px solid #e3e6ea;
            font-size: 7px;
            vertical-align: top;
        }

        table.items tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table.items th.text-end,
        table.items td.text-end {
            text-align: right !important;
            padding-right: 8px !important;
        }

        table.items th.text-center,
        table.items td.text-center {
            text-align: center !important;
        }

        .badge-mini {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 2px;
            font-size: 6px;
            color: #fff;
        }

        .badge-yes { background-color: #00d27a; }
        .badge-no { background-color: #adb5bd; }

        .totals {
            width: 45%;
            margin-left: 55%;
            border-collapse: collapse;
        }

        .totals td {
            padding: 4px 6px;
            font-size: 8px;
        }

        .totals .label {
            color: #666;
        }

        .totals .value {
            text-align: right;
            font-weight: bold;
        }

        .totals tr.total-row td {
            border-top: 2px solid #2c7be5;
            font-size: 10px;
            color: #2c7be5;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 6px;
            color: #999;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Rendición de Gastos {{ $report->number }}</h1>
        <p>{{ $report->team->name ?? '' }}</p>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-col">
                    <span class="info-label">Rendidor</span>
                    <span class="info-value">{{ $report->user->name ?? '—' }}</span>
                </div>
                <div class="info-col">
                    <span class="info-label">Fecha creación</span>
                    <span class="info-value">{{ $report->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="info-col">
                    <span class="info-label">Estado</span>
                    <span class="status-badge status-{{ $report->status }}">{{ $report->status_label }}</span>
                </div>
                <div class="info-col">
                    <span class="info-label">Aprobador asignado</span>
                    <span class="info-value">{{ $report->assignedTo->name ?? '—' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-col" style="width: 75%;" colspan="3">
                    <span class="info-label">Descripción</span>
                    <span class="info-value">{{ $report->description ?: '—' }}</span>
                </div>
                @if($report->approvedBy)
                <div class="info-col">
                    <span class="info-label">Aprobado por</span>
                    <span class="info-value">{{ $report->approvedBy->name }} el {{ $report->approved_at?->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>

        @if($report->rejection_notes)
        <div class="rejection-box">
            <strong>Motivo de rechazo:</strong> {{ $report->rejection_notes }}
        </div>
        @endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 7%;">Fecha</th>
                <th style="width: 16%;">Proveedor</th>
                <th style="width: 10%;">Tipo Doc.</th>
                <th style="width: 9%;">Nº Doc.</th>
                <th style="width: 13%;">Producto</th>
                <th style="width: 20%;">Descripción</th>
                <th style="width: 10%;" class="text-end">Monto</th>
                <th style="width: 7%;" class="text-center">Comp.</th>
                <th style="width: 8%;" class="text-center">Contab.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report->items as $item)
            <tr>
                <td>{{ $item->date->format('d/m/Y') }}</td>
                <td>{{ $item->supplier->name ?? '—' }}</td>
                <td>{{ $item->typeDocument->name ?? '—' }}</td>
                <td>{{ $item->document_number }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->description ?: '—' }}</td>
                <td class="text-end">${{ number_format($item->amount, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($item->receipt_path)
                        <span class="badge-mini badge-yes">Sí</span>
                    @else
                        <span class="badge-mini badge-no">No</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($item->is_contabilized)
                        <span class="badge-mini badge-yes">Sí</span>
                    @else
                        <span class="badge-mini badge-no">No</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center">Sin documentos registrados</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Total rendición</td>
            <td class="value">${{ number_format($report->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Contabilizado</td>
            <td class="value">${{ number_format($report->contabilized_amount, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td class="label">Pendiente</td>
            <td class="value">${{ number_format($report->pending_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer-note">
        Generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}
    </div>

</body>
</html>
