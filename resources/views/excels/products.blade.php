<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
            <th><b>Unidad</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
        <tr class="items">
            <td>{{ $product['name'] }}</td>
            <td>{{ $product['unit']['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>