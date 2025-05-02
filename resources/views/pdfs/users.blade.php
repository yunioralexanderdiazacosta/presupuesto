<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Usuarios</title>
    <link href="{{  public_path('assets/css/table.css') }}" rel="stylesheet" type="text/css">
</head>
<body>
    <h2 style="text-align: center;">Usuarios</h2>
    <div class="margin-top">
        <table class="table table-bordered">
            <tr align="left">
                <th>Nombre del contacto</th>
                <th>Correo Electrónico</th>
                <th>Rol</th>
                <th>F. Registro</th>
                <th>EStatus</th>
            </tr>
            @foreach($users as $user)
            <tr class="items">
                <td>{{ $user['name'] }}</td>
                <td>{{ $user['email'] }}</td>
                <td>{{ $user['role'] }}</td>
                <td>{{ date('d-m-Y h:ia', strtotime($user['created_at'])) }}</td>
                <td>{{ $user['status'] == 1 ? 'Activo' : 'Suspendido' }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>
</html>