<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
            <th><b>Superficie</b></th>
            <th><b>Observaciones</b></th>
        </tr>    
    </thead>
    <tbody>
        @foreach($costCenters as $costCenter)
        <tr class="items">
                <td>{{ $costCenter['name'] }}</td>
                <td>{{ $costCenter['surface'] }}</td>
                 <td>{{ $costCenter['observations'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
    