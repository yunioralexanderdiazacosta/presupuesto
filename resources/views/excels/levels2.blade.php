<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
            <th><b>Nivel 1</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($levels as $level)
        <tr class="items">
            <td>{{ $level['name'] }}</td>
            <td>{{ $level['level1']['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>