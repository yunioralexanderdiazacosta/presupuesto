<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Centros de Costos</title>
    <link href="{{  public_path('assets/css/table.css') }}" rel="stylesheet" type="text/css">
</head>
<body>
    
    <h2 style="text-align: center;">Centros de Costos</h2>
    <div class="margin-top">
        <table class="table table-bordered">
            <tr align="left">
                <th>Nombre</th>
                <th>Superficie</th>
                <th>Observaciones</th>
            </tr>
             @foreach($costCenters as $costCenter)
            <tr class="items">
                    <td>{{ $costCenter['name'] }}</td>
                    <td>{{ $costCenter['surface'] }}</td>
                     <td>{{ $costCenter['observations'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>
</html>