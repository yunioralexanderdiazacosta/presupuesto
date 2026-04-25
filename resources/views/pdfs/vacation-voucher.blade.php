<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Feriado - {{ $employee->paternal_surname }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            padding: 20px 30px;
        }

        /* ── Header ── */
        .header-wrap {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .header-title {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }
        .header-title h1 {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a1a;
            border: 2px solid #000;
            padding: 6px 12px;
            display: inline-block;
            background-color: #dce6f1;
        }
        .header-date {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 50%;
        }
        .date-table {
            border-collapse: collapse;
            margin-left: auto;
        }
        .date-table th, .date-table td {
            border: 1px solid #000;
            padding: 3px 8px;
            text-align: center;
        }
        .date-table th {
            background-color: #dce6f1;
            font-size: 9px;
            font-weight: bold;
        }
        .date-table td {
            font-size: 10px;
        }

        /* ── Intro text ── */
        .intro-box {
            border: 1px solid #000;
            padding: 6px 8px;
            margin-bottom: 8px;
            font-size: 10px;
            line-height: 1.5;
            background-color: #f9f9f9;
        }

        /* ── Worker info ── */
        .worker-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        .worker-cell {
            display: table-cell;
        }
        .worker-cell .label {
            font-weight: bold;
            margin-right: 4px;
        }
        .worker-cell.right {
            text-align: right;
        }

        /* ── Tipo de feriado ── */
        .tipo-row {
            margin-bottom: 2px;
            font-size: 10px;
        }
        .tipo-row .label {
            font-weight: bold;
            margin-right: 4px;
        }

        /* ── Main detail table ── */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 14px;
        }
        .main-table th, .main-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10px;
        }
        .main-table th {
            background-color: #dce6f1;
            font-weight: bold;
            text-align: center;
        }
        .main-table .col-desc { width: 60%; }
        .main-table .col-dias { width: 20%; text-align: center; }
        .main-table .col-valor { width: 20%; text-align: center; }
        .main-table .total-row td {
            font-weight: bold;
            background-color: #f0f0f0;
            text-align: center;
        }
        .main-table .total-row .label-cell {
            text-align: right;
            font-weight: bold;
        }
        .dates-row td { vertical-align: middle; }
        .dates-inner {
            display: table;
            width: 100%;
        }
        .dates-inner-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .date-label { font-weight: bold; margin-right: 4px; }
        .date-val { font-size: 11px; font-weight: bold; margin-right: 12px; }

        /* ── Bottom section ── */
        .bottom-wrap {
            display: table;
            width: 100%;
            margin-top: 6px;
        }
        .col-detalle {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-right: 10px;
        }
        .col-firmas {
            display: table-cell;
            width: 52%;
            vertical-align: top;
            padding-left: 10px;
        }

        /* ── Detalle table ── */
        .detalle-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detalle-table th {
            background-color: #dce6f1;
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 9px;
            font-weight: bold;
        }
        .detalle-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10px;
        }
        .detalle-table .col-item { width: 70%; }
        .detalle-table .col-dias { width: 30%; text-align: center; }

        /* ── Firmas ── */
        .firma-box {
            border: 1px solid #000;
            padding: 6px 8px;
            margin-bottom: 8px;
            min-height: 55px;
            text-align: center;
        }
        .firma-box .firma-title {
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            background-color: #dce6f1;
            padding: 3px;
            margin: -6px -8px 8px -8px;
        }
        .firma-box .firma-name {
            font-size: 11px;
            margin-top: 6px;
        }
        .firma-line {
            border-bottom: 1px solid #000;
            width: 80%;
            margin: 18px auto 0 auto;
        }

        .text-blue { color: #154360; }
        .fw-bold { font-weight: bold; }
        .underline { text-decoration: underline; }
    </style>
</head>
<body>

    {{-- ── HEADER ── --}}
    <div class="header-wrap">
        <div class="header-title">
            <h1>COMPROBANTE DE FERIADO</h1>
        </div>
        <div class="header-date">
            <table class="date-table">
                <thead>
                    <tr>
                        <th>LUGAR</th>
                        <th>DIA</th>
                        <th>MES</th>
                        <th>AÑO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $lugar ?: '___________' }}</td>
                        <td>{{ $today->day }}</td>
                        <td>{{ $meses[$today->month] }}</td>
                        <td>{{ $today->year }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── INTRO ── --}}
    <div class="intro-box">
        En el cumplimiento a las disposiciones legales vigentes se deja constancia que a contar de las fechas que se
        indican, el trabajador:
    </div>

    {{-- ── DATOS DEL TRABAJADOR ── --}}
    <div class="worker-row">
        <div class="worker-cell">
            <span class="label">Don:</span>
            <span class="underline fw-bold">{{ $employee->first_name }} {{ $employee->paternal_surname }} {{ $employee->maternal_surname }}</span>
        </div>
        <div class="worker-cell right">
            <span class="label">Rut:</span>
            <span class="fw-bold">{{ $employee->rut }}</span>
        </div>
    </div>

    {{-- ── TIPO DE FERIADO ── --}}
    <div class="tipo-row">
        <span class="label">hará uso:</span>
        <span class="fw-bold">
            Feriado {{ $tipoPeriodo }} período
            @if($periodStart && $periodEnd)
                {{ $periodStart }}-{{ $periodEnd }}
            @endif
        </span>
        <span style="font-style:italic; color:#555;">&nbsp;(parte o total)</span>
        &nbsp;de su
    </div>

    {{-- ── TABLA PRINCIPAL ── --}}
    <table class="main-table">
        <thead>
            <tr>
                <th class="col-desc" colspan="2">
                    Feriado Anual con remuneración íntegra de acuerdo al siguiente detalle:
                </th>
                <th class="col-dias">DIAS</th>
                <th class="col-valor">VALOR</th>
            </tr>
        </thead>
        <tbody>
            {{-- Descanso efectivo --}}
            <tr class="dates-row">
                <td class="col-desc" colspan="2">
                    <div class="dates-inner">
                        <div class="dates-inner-cell">
                            <span class="date-label">DESCANSO EFECTIVO ENTRE LAS FECHAS QUE SE INDICAN:</span>
                        </div>
                    </div>
                </td>
                <td class="col-dias" rowspan="2" style="font-size:14px; font-weight:bold; vertical-align:middle;">
                    {{ $diasHabiles }}
                </td>
                <td class="col-valor" rowspan="2" style="vertical-align:middle;">-</td>
            </tr>
            <tr>
                <td style="width:30%; padding: 3px 6px;">
                    <span class="date-label">DESDE EL:</span>
                    <span class="fw-bold">{{ $fechaInicio->format('d-m-Y') }}</span>
                </td>
                <td style="width:30%; padding: 3px 6px;">
                    <span class="date-label">AL</span>
                    <span class="fw-bold">{{ $fechaFin->format('d-m-Y') }}</span>
                </td>
            </tr>
            {{-- Feriado compensado --}}
            <tr>
                <td colspan="2">FERIADO COMPENSADO</td>
                <td class="col-dias">0</td>
                <td class="col-valor">-</td>
            </tr>
            {{-- Total --}}
            <tr class="total-row">
                <td colspan="2" class="label-cell">TOTAL</td>
                <td>{{ $diasHabiles }}</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>

    {{-- ── SECCIÓN INFERIOR ── --}}
    <div class="bottom-wrap">

        {{-- Detalle del feriado --}}
        <div class="col-detalle">
            <table class="detalle-table">
                <thead>
                    <tr>
                        <th class="col-item">DETALLE DEL FERIADO</th>
                        <th class="col-dias">DIAS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-item">DIAS HÁBILES</td>
                        <td class="col-dias">{{ $diasHabiles }}</td>
                    </tr>
                    <tr>
                        <td class="col-item">VACACIONES PROGRESIVAS</td>
                        <td class="col-dias">{{ $diasProgresivos > 0 ? $diasProgresivos : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="col-item">DOMINGO E INHÁBILES</td>
                        <td class="col-dias">{{ $diasInhabiles }}</td>
                    </tr>
                    <tr>
                        <td class="col-item">FERIADO FRACCIONADO</td>
                        <td class="col-dias">-</td>
                    </tr>
                    <tr>
                        <td class="col-item fw-bold">SALDO PENDIENTE</td>
                        <td class="col-dias fw-bold">{{ $saldoPendiente > 0 ? floor($saldoPendiente) : 0 }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Firmas --}}
        <div class="col-firmas">
            <div class="firma-box">
                <div class="firma-title">NOMBRE Y FIRMA DEL EMPLEADOR O EMPRESA</div>
                <div class="firma-name">{{ $teamName }}</div>
                <div class="firma-line"></div>
            </div>
            <div class="firma-box">
                <div class="firma-title">FIRMA DEL TRABAJADOR</div>
                <div class="firma-line"></div>
            </div>
        </div>

    </div>

</body>
</html>
