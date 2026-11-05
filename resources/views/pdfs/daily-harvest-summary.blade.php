<!doctype html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Resumen Diario de Cosecha</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h2 { text-align: center; margin: 0 0 4px 0; }
        h4 { margin: 16px 0 6px 0; }
        .sub { text-align: center; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; }
        th { background: #eee; text-align: left; }
        .r { text-align: right; }
        .kpis td { text-align: center; padding: 8px 4px; }
        .kpis .v { font-size: 15px; font-weight: bold; display: block; }
        .kpis .l { color: #666; font-size: 9px; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
    </style>
</head>
<body>
    @php
        $fmt = fn($v) => number_format((float) $v, 0, ',', '.');
        $kpis = $summary['kpis'];
    @endphp

    <h2>Resumen Diario de Cosecha</h2>
    <div class="sub">
        Fecha: {{ $dateLabel }}
        @if($branchNames->isNotEmpty()) &nbsp;|&nbsp; Sucursal: {{ $branchNames->implode(', ') }} @endif
        @if($reasonNames->isNotEmpty()) &nbsp;|&nbsp; Razón social: {{ $reasonNames->implode(', ') }} @endif
    </div>

    <table class="kpis">
        <tr>
            <td><span class="v">{{ $fmt($kpis['kg']) }}</span><span class="l">KG DESPACHADOS</span></td>
            <td><span class="v">{{ $kpis['guides'] }}</span><span class="l">GUÍAS</span></td>
            <td><span class="v">{{ $kpis['trucks'] }}</span><span class="l">CAMIONES</span></td>
            <td><span class="v">{{ $fmt($kpis['bins']) }}</span><span class="l">BINS</span></td>
            <td><span class="v">{{ $fmt($kpis['boxes']) }}</span><span class="l">CAJAS</span></td>
        </tr>
    </table>

    @foreach([
        'Kilos por variedad' => ['rows' => $summary['by_variety'], 'col' => 'Variedad'],
        'Kilos por exportadora' => ['rows' => $summary['by_exporter'], 'col' => 'Exportadora'],
        'Kilos por packing' => ['rows' => $summary['by_packing_house'], 'col' => 'Packing'],
    ] as $title => $block)
        <h4>{{ $title }}</h4>
        <table>
            <thead>
                <tr><th>{{ $block['col'] }}</th><th class="r">Guías</th><th class="r">Kg</th></tr>
            </thead>
            <tbody>
                @forelse($block['rows'] as $row)
                    <tr><td>{{ $row['name'] }}</td><td class="r">{{ $row['guides'] }}</td><td class="r">{{ $fmt($row['kg']) }}</td></tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:#888;">Sin guías para esta fecha</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <h4>Kilos por cuartel</h4>
    <table>
        <thead>
            <tr><th>Cuartel</th><th>Variedad</th><th class="r">Guías</th><th class="r">Kg</th></tr>
        </thead>
        <tbody>
            @forelse($summary['by_cost_center'] as $row)
                <tr><td>{{ $row['name'] }}</td><td>{{ $row['varieties'] }}</td><td class="r">{{ $row['guides'] }}</td><td class="r">{{ $fmt($row['kg']) }}</td></tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#888;">Sin guías para esta fecha</td></tr>
            @endforelse
        </tbody>
    </table>

    <h4>Últimos 7 días</h4>
    <table>
        <thead>
            <tr><th>Fecha</th><th class="r">Guías</th><th class="r">Kg</th></tr>
        </thead>
        <tbody>
            @foreach($summary['trend'] as $row)
                <tr><td>{{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }}</td><td class="r">{{ $row['guides'] }}</td><td class="r">{{ $fmt($row['kg']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
