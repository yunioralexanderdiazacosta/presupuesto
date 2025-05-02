<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
            <th><b>Razón Social</b></th>
            <th><b>Temporada</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($parcels as $parcel)
        <tr class="items">
            <td>{{ $parcel['name'] }}</td>
            <td>{{ $parcel['companyReason']['name'] }}</td>
            <td>{{ $parcel['season']['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>