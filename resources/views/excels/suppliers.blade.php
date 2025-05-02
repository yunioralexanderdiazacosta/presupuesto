<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
            <th><b>Rut</b></th>
            <th><b>Contacto</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($suppliers as $supplier)
            <tr class="items">
                <td>{{ $supplier['name'] }}</td>
                <td>{{ $supplier['rut'] }}</td>
                <td>{{ $supplier['contact'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>