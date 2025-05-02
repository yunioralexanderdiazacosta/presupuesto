
<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($typeMachineries as $value)
        <tr class="items">
            <td>{{ $value['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>