<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Compra {{ $purchaseOrder->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #333; padding: 15px; }

        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #2c7be5; padding-bottom: 8px; }
        .header h1 { font-size: 18px; color: #2c7be5; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #666; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 9px; font-weight: bold; color: #fff; }
        .badge-secondary { background-color: #6c757d; }
        .badge-warning   { background-color: #f5803e; }
        .badge-info      { background-color: #3b7ddd; }
        .badge-danger    { background-color: #e63757; }
        .badge-primary   { background-color: #2c7be5; }
        .badge-success   { background-color: #00d27a; }
        .badge-dark      { background-color: #12263f; }

        .section { margin-bottom: 10px; }
        .boxes { width: 100%; }
        .boxes td { vertical-align: top; padding: 0 4px; }
        .info-box { padding: 8px 10px; background-color: #f8f9fa; border-left: 3px solid #2c7be5; margin-bottom: 8px; }
        .info-box h2 { font-size: 10px; color: #2c7be5; text-transform: uppercase; margin-bottom: 6px; }
        .info-row { margin: 4px 0; }
        .info-label { font-weight: bold; color: #666; text-transform: uppercase; font-size: 7.5px; display: block; }
        .info-value { font-size: 10px; }

        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.items thead tr th {
            background-color: #2c7be5; color: #fff; font-size: 8px; font-weight: bold;
            text-transform: uppercase; padding: 5px 4px; border: 1px solid #1a56db; text-align: center;
        }
        table.items tbody tr td {
            padding: 4px; border: 1px solid #dde1e7; font-size: 9px; vertical-align: middle;
        }
        table.items tbody tr:nth-child(even) td { background-color: #f4f6fb; }
        table.items tfoot tr td {
            padding: 5px 4px; border: 1px solid #b0bfdb; font-size: 9.5px; font-weight: bold; background-color: #e8eef8;
        }
        table.items tfoot tr.total td { background-color: #d7e4fb; color: #1a56db; font-size: 11px; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #888; }

        .cost-centers { margin-top: 10px; font-size: 9px; }
        .cost-centers span.cc { display: inline-block; border: 1px solid #dde1e7; border-radius: 3px; padding: 2px 6px; margin: 2px; }

        .notes-box { margin-top: 10px; padding: 8px 10px; background-color: #f8f9fa; border-left: 3px solid #6c757d; font-size: 9.5px; white-space: pre-line; }

        .footer { text-align: center; font-size: 7.5px; color: #aaa; margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Orden de Compra {{ $purchaseOrder->order_number }}</h1>
        <p>{{ $purchaseOrder->team->name ?? '' }}</p>
        <p>
            <span class="badge badge-{{ $purchaseOrder->status_color }}">{{ $purchaseOrder->status_label }}</span>
        </p>
    </div>

    <table class="boxes">
        <tr>
            <td style="width:50%;">
                <div class="info-box">
                    <h2>Información de la Orden</h2>
                    <div class="info-row">
                        <span class="info-label">Razón Social (empresa pagadora)</span>
                        <span class="info-value">{{ $purchaseOrder->companyReason->name ?? 'No asignada' }}</span>
                    </div>
                    @if($purchaseOrder->companyReason && $purchaseOrder->companyReason->rut)
                    <div class="info-row">
                        <span class="info-label">RUT Empresa</span>
                        <span class="info-value">{{ $purchaseOrder->companyReason->rut }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Fecha Orden</span>
                        <span class="info-value">{{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d/m/Y') : '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha Entrega</span>
                        <span class="info-value">{{ $purchaseOrder->delivery_date ? $purchaseOrder->delivery_date->format('d/m/Y') : 'No especificada' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Condiciones de Pago</span>
                        <span class="info-value">{{ $purchaseOrder->payment_terms ?: '-' }}</span>
                    </div>
                </div>
            </td>
            <td style="width:50%;">
                <div class="info-box">
                    <h2>Proveedor</h2>
                    @if($purchaseOrder->supplier)
                        <div class="info-row">
                            <span class="info-label">Nombre</span>
                            <span class="info-value">{{ $purchaseOrder->supplier->name }}</span>
                        </div>
                        @if($purchaseOrder->supplier->rut)
                        <div class="info-row">
                            <span class="info-label">RUT</span>
                            <span class="info-value">{{ $purchaseOrder->supplier->rut }}</span>
                        </div>
                        @endif
                        @if($purchaseOrder->supplier->contact)
                        <div class="info-row">
                            <span class="info-label">Contacto</span>
                            <span class="info-value">{{ $purchaseOrder->supplier->contact }}</span>
                        </div>
                        @endif
                        @if($purchaseOrder->supplier->email)
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value">{{ $purchaseOrder->supplier->email }}</span>
                        </div>
                        @endif
                        @if($purchaseOrder->supplier->phone)
                        <div class="info-row">
                            <span class="info-label">Teléfono</span>
                            <span class="info-value">{{ $purchaseOrder->supplier->phone }}</span>
                        </div>
                        @endif
                    @else
                        <span class="text-muted">Sin proveedor asignado</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="text-left">Producto</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Unidad</th>
                <th class="text-right">P. Unitario</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrder->items as $item)
            <tr>
                <td>
                    {{ $item->product->name ?? 'Producto no encontrado' }}
                    @if($item->notes)
                        <br><span class="text-muted" style="font-size:8px;">{{ $item->notes }}</span>
                    @endif
                </td>
                <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 3, ',', '.'), '0'), ',') }}</td>
                <td class="text-center">{{ $item->unit->name ?? '-' }}</td>
                <td class="text-right">$ {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">$ {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted" style="padding:10px;">Sin productos</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Subtotal</td>
                <td class="text-right">$ {{ number_format($purchaseOrder->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="4" class="text-right">IVA (19%)</td>
                <td class="text-right">$ {{ number_format($purchaseOrder->tax, 0, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td colspan="4" class="text-right">TOTAL</td>
                <td class="text-right">$ {{ number_format($purchaseOrder->total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if($purchaseOrder->costCenters->count())
    <div class="cost-centers">
        <span class="info-label">Centros de Costo</span>
        @foreach($purchaseOrder->costCenters as $cc)
            <span class="cc">{{ $cc->name }}</span>
        @endforeach
    </div>
    @endif

    <table class="boxes" style="margin-top:10px;">
        <tr>
            <td style="width:50%;">
                <div class="info-row">
                    <span class="info-label">Solicitado por</span>
                    <span class="info-value">{{ $purchaseOrder->requestedBy->name ?? '-' }}</span>
                </div>
            </td>
            <td style="width:50%;">
                @if($purchaseOrder->approvedBy)
                <div class="info-row">
                    <span class="info-label">Aprobado por</span>
                    <span class="info-value">{{ $purchaseOrder->approvedBy->name }}</span>
                </div>
                @endif
            </td>
        </tr>
    </table>

    @if($purchaseOrder->notes)
    <div class="notes-box">
        <span class="info-label">Observaciones</span>
        {{ $purchaseOrder->notes }}
    </div>
    @endif

    <div class="footer">
        Documento generado el {{ now()->format('d/m/Y H:i') }} &middot; Alisoft &mdash; Software de Gestión Agrícola
    </div>
</body>
</html>
