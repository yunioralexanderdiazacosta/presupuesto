<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Maquinarias</title>
    <link href="{{  public_path('assets/css/table.css') }}" rel="stylesheet" type="text/css">
</head>
<body>
    
    <h2 style="text-align: center;">Maquinarias</h2>
    <div class="margin-top">
        <table class="table table-bordered">
            <tr align="left">
                <th>Código</th>
                <th>Tipo</th>
                <th>Marca</th>
                <th>Status</th>
            </tr>
             @foreach($machineries as $machinary)
            <tr class="items">
                <td>{{ $machinary['cod_machinery'] }}</td>
                <td>{{ $machinary['typeMachinery']['name'] }}</td>
                <td>{{ $machinary['brand'] }}</td>
                <td>{{ $machinary['is_active'] == true ? 'Activo' : 'INactivo' }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>
</html>