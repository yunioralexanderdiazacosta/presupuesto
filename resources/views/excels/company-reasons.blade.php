<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
            <th><b>Rut</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($companyReasons as $companyReason)
        <tr>
            <td>{{ $companyReason['name'] }}</td>
            <td>{{ $companyReason['rut'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
    