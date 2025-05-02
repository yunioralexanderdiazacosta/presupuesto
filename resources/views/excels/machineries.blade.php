<table>
    <thead>
        <tr>
            <th><b>Código</b></th>
            <th><b>Tipo</b></th>
            <th><b>Marca</b></th>
            <th><b>Status</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($machineries as $machinary)
        <tr class="items">
            <td>{{ $machinary['cod_machinery'] }}</td>
            <td>{{ $machinary['typeMachinery']['name'] }}</td>
            <td>{{ $machinary['brand'] }}</td>
            <td>{{ $machinary['is_active'] == true ? 'Activo' : 'INactivo' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>