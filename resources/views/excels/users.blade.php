<table>
    <thead>
        <tr>
            <th><b>Nombre del contacto</b></th>
            <th><b>Correo Electrónico</b></th>
            <th><b>Rol</b></th>
            <th><b>F. Registro</b></th>
            <th><b>EStatus</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        <tr class="items">
            <td>{{ $user['name'] }}</td>
            <td>{{ $user['email'] }}</td>
            <td>{{ $user['role'] }}</td>
            <td>{{ date('d-m-Y h:ia', strtotime($user['created_at'])) }}</td>
            <td>{{ $user['status'] == 1 ? 'Activo' : 'Suspendido' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>