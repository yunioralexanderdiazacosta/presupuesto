<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
        </tr>    
    </thead>
    <tbody>
        @foreach($levels as $level)
        <tr class="items">
                <td>{{ $level['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>