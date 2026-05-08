<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plantilla Tarjas - {{ $date->format('d/m/Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8px; }
        .header { text-align: center; margin-bottom: 8px; }
        .header h2 { font-size: 14px; margin-bottom: 2px; }
        .header .info { font-size: 10px; color: #333; }
        .meta-row { display: table; width: 100%; margin-bottom: 6px; font-size: 9px; }
        .meta-cell { display: table-cell; }
        .meta-cell.right { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { border: 1px solid #333; padding: 2px 3px; }
        th { background-color: #e0e0e0; font-size: 7px; text-align: center; font-weight: bold; }
        td { font-size: 8px; height: 20px; }
        .num { text-align: center; width: 25px; }
        .name { min-width: 48px; }
        .rut { width: 65px; font-size: 7px; }
        .cc-col { width: 30px; text-align: center; }
        .id-agrup-col { width: 28px; text-align: center; }
        .labor-col { width: 70px; }
        .trato-col { width: 50px; }
        .cant-col { width: 35px; text-align: center; }
        .hrs-col { width: 30px; text-align: center; }
        .precip-col { width: 40px; text-align: center; }
        .obs-col { width: 80px; }
        .labor-header { background-color: #c8d8e8; }
        .labor-header-2 { background-color: #d8e8c8; }
        .footer { margin-top: 20px; font-size: 9px; }
        .signature-line { display: inline-block; width: 200px; border-bottom: 1px solid #333; margin-top: 30px; text-align: center; }
        .page-break { page-break-after: always; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    @php
        $perPage = 30;
        $chunks = $contracts->values()->chunk($perPage);
        $totalPages = $chunks->count() ?: 1;
        $pageNum = 0;
    @endphp

    @forelse($chunks as $chunk)
        @php $pageNum++; @endphp
        <div class="{{ !$loop->last ? 'page-break' : '' }}">
            {{-- Header --}}
            <div class="header">
                <h2>PLANTILLA DE TARJAS</h2>
                <div class="info">
                    <strong>{{ $teamName }}</strong> &nbsp;|&nbsp;
                    Fecha: <strong>{{ $date->format('d/m/Y') }}</strong> &nbsp;|&nbsp;
                    Día: <strong>{{ ucfirst($date->locale('es')->isoFormat('dddd')) }}</strong> &nbsp;|&nbsp;
                    Parcela: <strong>{{ $parcelName }}</strong> &nbsp;|&nbsp;
                    Pág {{ $pageNum }}/{{ $totalPages }}
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="num" rowspan="2">#</th>
                        <th class="name" rowspan="2">Nombre</th>
                        <th class="rut" rowspan="2">RUT</th>
                        <th colspan="6" class="labor-header">Labor 1</th>
                        <th colspan="6" class="labor-header-2">Labor 2</th>
                        <th class="precip-col" rowspan="2">Precip. Obj.</th>
                        <th class="obs-col" rowspan="2">Obs.</th>
                    </tr>
                    <tr>
                        <th class="id-agrup-col labor-header">ID Agr.</th>
                        <th class="cc-col labor-header">CC</th>
                        <th class="labor-col labor-header">Labor</th>
                        <th class="trato-col labor-header">Trato</th>
                        <th class="cant-col labor-header">Cant</th>
                        <th class="hrs-col labor-header">Hrs</th>
                        <th class="id-agrup-col labor-header-2">ID Agr.</th>
                        <th class="cc-col labor-header-2">CC</th>
                        <th class="labor-col labor-header-2">Labor</th>
                        <th class="trato-col labor-header-2">Trato</th>
                        <th class="cant-col labor-header-2">Cant</th>
                        <th class="hrs-col labor-header-2">Hrs</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chunk as $index => $contract)
                        <tr>
                            <td class="num">{{ ($pageNum - 1) * $perPage + $loop->iteration }}</td>
                            <td class="name">{{ $contract->employee->paternal_surname }} {{ $contract->employee->maternal_surname }}, {{ $contract->employee->first_name }}</td>
                            <td class="rut">{{ $contract->employee->rut }}</td>
                            <td class="id-agrup-col"></td>
                            <td class="cc-col"></td>
                            <td class="labor-col"></td>
                            <td class="trato-col"></td>
                            <td class="cant-col"></td>
                            <td class="hrs-col"></td>
                            <td class="id-agrup-col"></td>
                            <td class="cc-col"></td>
                            <td class="labor-col"></td>
                            <td class="trato-col"></td>
                            <td class="cant-col"></td>
                            <td class="hrs-col"></td>
                            <td class="precip-col"></td>
                            <td class="obs-col"></td>
                        </tr>
                    @endforeach
                    {{-- Filas vacías para completar si hay menos de perPage --}}
                    @for($i = $chunk->count(); $i < $perPage; $i++)
                        <tr>
                            <td class="num">{{ ($pageNum - 1) * $perPage + $i + 1 }}</td>
                            <td class="name"></td>
                            <td class="rut"></td>
                            <td class="id-agrup-col"></td>
                            <td class="cc-col"></td>
                            <td class="labor-col"></td>
                            <td class="trato-col"></td>
                            <td class="cant-col"></td>
                            <td class="hrs-col"></td>
                            <td class="id-agrup-col"></td>
                            <td class="cc-col"></td>
                            <td class="labor-col"></td>
                            <td class="trato-col"></td>
                            <td class="cant-col"></td>
                            <td class="hrs-col"></td>
                            <td class="precip-col"></td>
                            <td class="obs-col"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            {{-- Footer --}}
            <div class="footer">
                <div class="meta-row">
                    <div class="meta-cell">
                        <span class="signature-line">&nbsp;</span><br>
                        <small>Firma Supervisor</small>
                    </div>
                    <div class="meta-cell right">
                        <small>Generado: {{ now()->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="header">
            <h2>PLANTILLA DE TARJAS</h2>
            <p>No hay trabajadores con contrato activo{{ $parcelName !== 'Todas' ? ' en parcela ' . $parcelName : '' }}.</p>
        </div>
    @endforelse
</body>
</html>
