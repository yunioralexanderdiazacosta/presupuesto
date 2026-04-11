<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Labores</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; color: #333; }

        .header {
            background-color: #2c7be5;
            color: white;
            padding: 12px 20px;
            margin-bottom: 15px;
        }
        .header h1 { font-size: 16px; margin-bottom: 2px; }
        .header .meta { font-size: 8px; opacity: 0.9; }

        .summary {
            display: flex;
            margin: 0 20px 12px;
            font-size: 8px;
            color: #666;
        }
        .summary span { margin-right: 20px; }

        .group { margin: 0 20px 14px; page-break-inside: avoid; }

        table { width: 100%; border-collapse: collapse; }
        th {
            background-color: #f9fafd;
            border-bottom: 1.5px solid #d8e2ef;
            text-align: left;
            padding: 4px 8px;
            font-size: 8px;
            color: #5e6e82;
            text-transform: uppercase;
        }
        td {
            padding: 5px 8px;
            border-bottom: 0.5px solid #e3e6f0;
            font-size: 9px;
        }

        .code-cell {
            font-weight: bold;
            color: #2c7be5;
            font-size: 12px;
            text-align: center;
            width: 50px;
        }
        .name-cell { font-weight: 600; }
        .level3-cell {
            color: #2c7be5;
            font-weight: 600;
            font-size: 9px;
            background-color: #f0f4fb;
            vertical-align: middle;
        }
        .unit-cell { color: #748194; font-size: 8px; }
        .rate-cell { text-align: right; font-size: 8px; color: #748194; }

        .group-count {
            font-size: 7px;
            color: #748194;
            font-weight: normal;
            margin-left: 8px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7px;
            color: #aaa;
            padding: 5px;
            border-top: 0.5px solid #e3e6f0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i>&#9881;</i> Catálogo de Labores</h1>
        <div class="meta">
            {{ $teamName }} &nbsp;|&nbsp; Generado: {{ $date }} &nbsp;|&nbsp; Total: {{ $totalLabors }} labores activas
        </div>
    </div>

    <div class="group">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">Cód.</th>
                    <th>Labor</th>
                    <th style="width: 100px;">Nivel 3</th>
                    <th style="width: 80px;">Unidad</th>
                    <th style="width: 70px; text-align: right;">Tarifa Ref.</th>
                    <th style="width: 65px; text-align: right;">Bono Ref.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grouped as $groupName => $labors)
                    @foreach($labors->sortBy('code') as $lt)
                        <tr>
                            <td class="code-cell">{{ $lt->code }}</td>
                            <td class="name-cell">{{ $lt->name }}</td>
                            @if($loop->first)
                                <td class="level3-cell" rowspan="{{ $labors->count() }}">{{ $groupName }}</td>
                            @endif
                            <td class="unit-cell">{{ $lt->unit?->name ?? '-' }}</td>
                            <td class="rate-cell">{{ $lt->default_rate ? '$' . number_format($lt->default_rate, 0, ',', '.') : '-' }}</td>
                            <td class="rate-cell">{{ $lt->default_bonus ? '$' . number_format($lt->default_bonus, 0, ',', '.') : '-' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Catálogo de Labores — {{ $teamName }} — {{ $date }}
    </div>
</body>
</html>
