<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Remuneraciones - {{ $monthLabel }}</title>
    <style>
        @page { margin: 20px 24px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; padding: 20px 24px; }

        .page-break { page-break-after: always; }

        .page-header {
            background: #1a3c5e;
            color: white;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .page-header h1 { font-size: 14px; font-weight: bold; }
        .page-header p { font-size: 9px; opacity: 0.85; margin-top: 2px; }

        .employee-info { display: table; width: 100%; margin-bottom: 10px; border: 1px solid #dee2e6; border-radius: 4px; }
        .employee-info-row { display: table-row; }
        .employee-info-cell { display: table-cell; padding: 6px 10px; border-right: 1px solid #dee2e6; vertical-align: top; width: 25%; }
        .employee-info-cell:last-child { border-right: none; }
        .info-label { font-size: 8px; color: #666; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-size: 9.5px; font-weight: bold; color: #1a3c5e; }

        .kpi-row { display: table; width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .kpi-cell { display: table-cell; border: 1px solid #dee2e6; padding: 5px 8px; text-align: center; background: #f8f9fa; }
        .kpi-cell.neto { background: #1a3c5e; color: white; }
        .kpi-cell.descuento { background: #fff3cd; }
        .kpi-label { font-size: 7.5px; text-transform: uppercase; margin-bottom: 3px; opacity: 0.75; }
        .kpi-cell.neto .kpi-label { opacity: 0.85; }
        .kpi-value { font-size: 10px; font-weight: bold; }

        .section-title { background: #e9ecef; padding: 4px 8px; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #495057; margin-bottom: 4px; margin-top: 10px; border-left: 3px solid #1a3c5e; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        th { padding: 4px 6px; text-align: left; font-size: 8px; font-weight: bold; color: white; }
        th.right, td.right { text-align: right; }
        th.center, td.center { text-align: center; }
        td { padding: 3px 6px; border-bottom: 1px solid #f0f0f0; font-size: 8.5px; vertical-align: top; }
        tr:nth-child(even) td { background: #f9f9f9; }

        .thead-blue th  { background: #1a3c5e; }
        .thead-green th  { background: #1a3c5e; }
        .thead-orange th { background: #1a3c5e; color: white; }

        tfoot.tfoot-blue td  { background: #1a3c5e !important; color: white; font-weight: bold; padding: 4px 6px; font-size: 8.5px; }
        tfoot.tfoot-green td  { background: #1a3c5e !important; color: white; font-weight: bold; padding: 4px 6px; font-size: 8.5px; }
        tfoot.tfoot-orange td { background: #1a3c5e !important; color: white; font-weight: bold; padding: 4px 6px; font-size: 8.5px; }

        .col-left { float: left; width: 48%; margin-right: 2%; }
        .col-right { float: left; width: 50%; }
        .clearfix { clear: both; display: block; height: 1px; }

        .total-neto-box { margin-top: 12px; border: 2px solid #1a3c5e; padding: 8px 12px; display: table; width: 100%; }
        .total-neto-label { font-size: 11px; font-weight: bold; color: #1a3c5e; display: table-cell; }
        .total-neto-value { font-size: 14px; font-weight: bold; color: #1a3c5e; display: table-cell; text-align: right; }

        .empty-section { color: #999; font-style: italic; font-size: 8px; padding: 4px 0; }

        .footer { margin-top: 20px; border-top: 1px solid #dee2e6; padding-top: 6px; font-size: 7.5px; color: #888; text-align: center; }
    </style>
</head>
<body>

@foreach($reports as $i => $r)
@php
    $employee         = $r['employee'];
    $dates            = $r['dates'];
    $days             = $r['days'];
    $monthlyBonuses   = $r['monthlyBonuses'];
    $monthlyDiscounts = $r['monthlyDiscounts'];
    $overtimeHours    = $r['overtimeHours'];
    $totals           = $r['totals'];
    $daysWithData     = array_filter($days, fn($d) => count($d['lines']) > 0);
    $isLast           = $i === count($reports) - 1;
@endphp

<div class="{{ $isLast ? '' : 'page-break' }}">

    <div class="page-header">
        <h1>Detalle de Remuneraciones &mdash; {{ $monthLabel }}</h1>
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="employee-info">
        <div class="employee-info-row">
            <div class="employee-info-cell">
                <div class="info-label">Nombre</div>
                <div class="info-value">{{ $employee['full_name'] }}</div>
            </div>
            <div class="employee-info-cell">
                <div class="info-label">RUT</div>
                <div class="info-value">{{ $employee['rut'] }}</div>
            </div>
            <div class="employee-info-cell">
                <div class="info-label">Contrato</div>
                <div class="info-value">
                    @if($employee['contract_id'])
                        <span style="background:#dbeafe;color:#1e40af;padding:1px 5px;border-radius:3px;font-size:9px;">#{{ $employee['contract_id'] }}</span>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="employee-info-cell">
                <div class="info-label">Cargo</div>
                <div class="info-value">{{ $employee['position'] ?: '—' }}</div>
            </div>
            <div class="employee-info-cell">
                <div class="info-label">Razón Social</div>
                <div class="info-value">{{ $employee['company_reason'] ?: '—' }}</div>
            </div>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-cell">
            <div class="kpi-label">Tratos</div>
            <div class="kpi-value">$ {{ number_format($totals['tratos'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-label">Monto Día</div>
            <div class="kpi-value">$ {{ number_format($totals['monto_dia'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-label">Bonos Diarios</div>
            <div class="kpi-value">$ {{ number_format($totals['bonus_diario'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-label">Bonos Objetivo</div>
            <div class="kpi-value">$ {{ number_format($totals['bonus_objetivo'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-label">Bonos Mens.</div>
            <div class="kpi-value">$ {{ number_format($totals['bonus_mensual'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-label">HH.EE.</div>
            <div class="kpi-value">$ {{ number_format($totals['horas_extra'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-cell descuento">
            <div class="kpi-label">Descuentos</div>
            <div class="kpi-value">- $ {{ number_format($totals['descuentos'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-cell neto">
            <div class="kpi-label">Total Neto</div>
            <div class="kpi-value">$ {{ number_format($totals['neto'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="section-title">Tarjas Diarias</div>

    @if(empty($daysWithData))
        <p class="empty-section">Sin tarjas registradas para este mes.</p>
    @else
        <table>
            <thead class="thead-blue">
                <tr>
                    <th style="width:12%">Fecha</th>
                    <th style="width:14%">Tipo Labor</th>
                    <th style="width:14%">Tarifa / Tipo Pago</th>
                    <th style="width:10%" class="right">Tarifa $</th>
                    <th style="width:7%" class="right">Cantidad</th>
                    <th style="width:7%" class="right">Jornada</th>
                    <th style="width:9%" class="right">Total Trato</th>
                    <th style="width:10%" class="center">Nombre Bono</th>
                    <th style="width:8%" class="right">Monto Bono</th>
                    <th style="width:8%" class="right">Precio Objetivo</th>
                    <th style="width:8%" class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dates as $date)
                    @if(count($days[$date]['lines']) > 0)
                        @foreach($days[$date]['lines'] as $li)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</td>
                                <td>{{ $li['labor_type'] ?? '—' }}</td>
                                <td>
                                    {{ $li['labor_rate'] ?? '—' }}
                                    @if($li['payment_type'] === 'dia')
                                        <span style="color:#888;">(día)</span>
                                    @endif
                                </td>
                                <td class="right">{{ $li['rate'] ? number_format($li['rate'], 0, ',', '.') : '—' }}</td>
                                <td class="right">{{ $li['payment_type'] === 'trato' ? $li['quantity'] : '—' }}</td>
                                <td class="right">{{ $li['payment_type'] === 'dia' ? $li['workdays'] : '—' }}</td>
                                <td class="right">
                                    @if($li['payment_type'] === 'trato')
                                        $ {{ number_format($li['amount'], 0, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="center">{{ $li['bonus_type'] ?? '' }}</td>
                                <td class="right">{{ $li['bonus_amount'] > 0 ? '$ '.number_format($li['bonus_amount'], 0, ',', '.') : '—' }}</td>
                                <td class="right">{{ $li['target_price_bonus'] > 0 ? '$ '.number_format($li['target_price_bonus'], 0, ',', '.') : '—' }}</td>
                                <td class="right"><strong>$ {{ number_format($li['amount'] + $li['bonus_amount'] + $li['target_price_bonus'], 0, ',', '.') }}</strong></td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
            <tfoot class="tfoot-blue">
                <tr>
                    <td colspan="6">TOTALES</td>
                    <td class="right">$ {{ number_format($totals['tratos'] + $totals['monto_dia'], 0, ',', '.') }}</td>
                    <td></td>
                    <td class="right">$ {{ number_format($totals['bonus_diario'], 0, ',', '.') }}</td>
                    <td class="right">$ {{ number_format($totals['bonus_objetivo'], 0, ',', '.') }}</td>
                    <td class="right">$ {{ number_format($totals['tratos'] + $totals['monto_dia'] + $totals['bonus_diario'] + $totals['bonus_objetivo'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div style="margin-top: 12px;">
        <div class="col-left" style="width:32%; margin-right:2%;">
            @if(count($monthlyDiscounts) > 0)
                <div class="section-title">Descuentos Mensuales</div>
                <table>
                    <thead class="thead-orange">
                        <tr>
                            <th style="width:55%">Tipo de Descuento</th>
                            <th style="width:45%" class="right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyDiscounts as $d)
                            <tr>
                                <td>{{ $d['type'] }}</td>
                                <td class="right">- $ {{ number_format($d['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="tfoot-orange">
                        <tr>
                            <td>TOTAL DESCUENTOS</td>
                            <td class="right">- $ {{ number_format($totals['descuentos'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        <div class="col-left" style="width:34%; margin-right:2%;">
            @if(count($monthlyBonuses) > 0)
                <div class="section-title">Bonos Mensuales</div>
                <table>
                    <thead class="thead-green">
                        <tr>
                            <th style="width:45%">Tipo de Bono</th>
                            <th style="width:30%">Labor</th>
                            <th style="width:25%" class="right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyBonuses as $b)
                            <tr>
                                <td>{{ $b['type'] }}</td>
                                <td>{{ $b['labor_type'] ?? '—' }}</td>
                                <td class="right">$ {{ number_format($b['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="tfoot-green">
                        <tr>
                            <td colspan="2">TOTAL BONOS MENSUALES</td>
                            <td class="right">$ {{ number_format($totals['bonus_mensual'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        <div class="col-right" style="width:30%;">
            @if(count($overtimeHours) > 0)
                <div class="section-title">Horas Extra</div>
                <table>
                    <thead class="thead-green">
                        <tr>
                            <th style="width:50%">Tipo</th>
                            <th style="width:20%" class="center">Horas</th>
                            <th style="width:30%" class="right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overtimeHours as $o)
                            <tr>
                                <td>{{ $o['type'] }}</td>
                                <td class="center">{{ $o['hours'] }}</td>
                                <td class="right">$ {{ number_format($o['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="tfoot-green">
                        <tr>
                            <td colspan="2">TOTAL HORAS EXTRA</td>
                            <td class="right">$ {{ number_format($totals['horas_extra'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        <div class="clearfix"></div>
    </div>

    <div class="total-neto-box">
        <div class="total-neto-label">TOTAL NETO A PAGAR &mdash; {{ $monthLabel }}</div>
        <div class="total-neto-value">$ {{ number_format($totals['neto'], 0, ',', '.') }}</div>
    </div>

    <div class="footer">
        {{ $employee['full_name'] }} &bull; {{ $employee['rut'] }} &bull; {{ $monthLabel }}
        &bull; Generado el {{ now()->format('d/m/Y H:i') }}
    </div>

</div>
@endforeach

</body>
</html>
