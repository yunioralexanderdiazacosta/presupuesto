<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
            <th><b>Mes de inicio</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($seasons as $season)
            <tr class="items">
                <td>{{ $season['name'] }}</td>
                <td>{{ $season['month']['name'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>