<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
            <th><b>Unidad</b></th>
            <th><b>Nivel 2</b></th>
            <th><b>Nivel 3</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
        <tr class="items">
            <td>{{ $product['name'] }}</td>
            <td>{{ $product['unit']['name'] }}</td>
            <td>{{ $product['level2']['name'] }}</td>
            <td>{{ $product['level3']['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>