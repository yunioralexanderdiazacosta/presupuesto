<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libro de Campo - Registro y Aplicación de Agroquímicos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            line-height: 1.2;
            color: #333;
            padding: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #2e7d32;
            padding-bottom: 6px;
        }

        .header h1 {
            font-size: 14px;
            color: #2e7d32;
            margin-bottom: 2px;
        }

        .header p {
            font-size: 8px;
            color: #666;
        }

        .cuartel-block {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .cuartel-header {
            background-color: #e8f5e9;
            border-left: 3px solid #2e7d32;
            padding: 4px 8px;
            margin-bottom: 0;
        }

        .cuartel-header h2 {
            font-size: 10px;
            color: #2e7d32;
            display: inline;
        }

        .cuartel-meta {
            font-size: 7px;
            color: #666;
            margin-left: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        table th {
            background-color: #f5f5f5;
            color: #333;
            font-weight: bold;
            font-size: 6.5px;
            text-transform: uppercase;
            padding: 3px 2px;
            border: 1px solid #ccc;
            text-align: center;
        }

        table td {
            padding: 2px 2px;
            border: 1px solid #ddd;
            font-size: 7px;
            vertical-align: middle;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-muted { color: #888; }
        .fw-bold { font-weight: bold; }

        .footer {
            text-align: center;
            font-size: 7px;
            color: #999;
            margin-top: 10px;
            border-top: 1px solid #eee;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REGISTRO Y APLICACIÓN DE AGROQUÍMICOS</h1>
        <p>Libro de Campo | Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @forelse($libroCampo as $cc)
        <div class="cuartel-block">
            <div class="cuartel-header">
                <h2>{{ $cc['cuartel'] }}</h2>
                <span class="cuartel-meta">
                    {{ $cc['fruta'] }} - {{ $cc['variedad'] }} |
                    {{ number_format($cc['superficie'], 2, ',', '.') }} Ha |
                    {{ count($cc['rows']) }} aplicaciones
                </span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Fecha Aplic.</th>
                        <th>Límite Protec.</th>
                        <th>Orden</th>
                        <th>Producto</th>
                        <th>Ingrediente Activo</th>
                        <th>Carencia</th>
                        <th>Reingreso</th>
                        <th>Cosecha desde</th>
                        <th>Tractor</th>
                        <th>Equipo</th>
                        <th>Operario</th>
                        <th>Dosis/100L</th>
                        <th>Dosis/Ha</th>
                        <th>Unidad</th>
                        <th>Moj. Real</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cc['rows'] as $row)
                        <tr>
                            <td class="text-center">{{ $row['fecha_aplic'] ? \Carbon\Carbon::parse($row['fecha_aplic'])->format('d/m/Y') : '-' }}</td>
                            <td class="text-center">{{ $row['limite_proteccion'] ? \Carbon\Carbon::parse($row['limite_proteccion'])->format('d/m/Y') : '-' }}</td>
                            <td class="text-center">#{{ $row['orden_id'] ?? '-' }}</td>
                            <td class="fw-bold">{{ $row['producto'] }}</td>
                            <td class="text-muted">{{ $row['ingrediente_activo'] ?? '-' }}</td>
                            <td class="text-center">{{ $row['carencia'] ?? '-' }}</td>
                            <td class="text-center">{{ $row['reingreso'] ?? '-' }}</td>
                            <td class="text-center">{{ $row['cosecha_desde'] ? \Carbon\Carbon::parse($row['cosecha_desde'])->format('d/m/Y') : '-' }}</td>
                            <td>{{ $row['tractor'] ?? '-' }}</td>
                            <td>{{ $row['equipo'] ?? '-' }}</td>
                            <td>{{ $row['operario'] ?? '-' }}</td>
                            <td class="text-end">{{ $row['dosis_100'] ? number_format($row['dosis_100'], 2, ',', '.') : '-' }}</td>
                            <td class="text-end">{{ $row['dosis_ha'] ? number_format($row['dosis_ha'], 2, ',', '.') : '-' }}</td>
                            <td class="text-center">{{ $row['unidad'] }}</td>
                            <td class="text-end">{{ $row['mojamiento'] ? number_format($row['mojamiento'], 2, ',', '.') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p style="text-align: center; padding: 20px; color: #999;">No hay registros de aplicaciones de agroquímicos para esta temporada.</p>
    @endforelse

    <div class="footer">
        Libro de Campo &mdash; Sistema de Gestión Presupuestaria
    </div>
</body>
</html>
