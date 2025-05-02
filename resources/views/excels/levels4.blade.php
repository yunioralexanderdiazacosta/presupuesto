<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
            <th><b>Nivel 3</b></th>
            <th><b>Nivel 2</b></th>
            <th><b>Nivel 1</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($levels as $level)
        <tr class="items">
            <td>{{ $level['name'] }}</td>
            <td>{{ $level['level3']['name'] }}</td>
            <td>{{ $level['level3']['level2']['name'] }}</td>
            <td>{{ $level['level3']['level2']['level1']['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>