<table>
    <thead>
        <tr>
            <th><b>Código</b></th>
            <th><b>Tipo</b></th>
            <th><b>Razón Social</b></th>
            <th><b>Contador</b></th>
            <th><b>Marca</b></th>
            <th><b>Modelo</b></th>
            <th><b>Patente</b></th>
            <th><b>Volumen</b></th>
            <th><b>Status</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($machineries as $machinary)
        <tr class="items">
            <td>{{ $machinary['cod_machinery'] }}</td>
            <td>{{ $machinary->typeMachinery->name ?? '-' }}</td>
            <td>{{ $machinary->companyReason->name ?? '-' }}</td>
            <td>{{ $machinary->counter->name ?? '-' }}</td>
            <td>{{ $machinary['brand'] ?? '-' }}</td>
            <td>{{ $machinary['modelo'] ?? '-' }}</td>
            <td>{{ $machinary['patente'] ?? '-' }}</td>
            <td>{{ $machinary['volume'] ?? '-' }}</td>
            <td>{{ $machinary['is_active'] == true ? 'Activo' : 'Inactivo' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>