<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($fruits as $fruit)
        <tr class="items">
            <td>{{ $fruit['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>