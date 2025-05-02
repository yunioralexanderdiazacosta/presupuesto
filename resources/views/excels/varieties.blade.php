<table class="table table-bordered">
    <thead>
        <tr align="left">
            <th><b>Nombre</b></th>
            <th><b>Frutal</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($varieties as $variety)
        <tr class="items">
            <td>{{ $variety['name'] }}</td>
            <td>{{ $variety['fruit']['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>