<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Productos</title>
    <link href="{{  public_path('assets/css/table.css') }}" rel="stylesheet" type="text/css">
</head>
<body>
    <h2 style="text-align: center;">Productos</h2>
    <div class="margin-top">
        <table class="table table-bordered">
            <tr align="left">
                <th>Nombre</th>
                <th>Unidad</th>
                <th>Nivel 2</th>
                <th>Nivel 3</th>
            </tr>
            @foreach($products as $product)
            <tr class="items">
                <td>{{ $product['name'] }}</td>
                <td>{{ $product['unit']['name'] }}</td>
                <td>{{ $product['level2']['name'] }}</td>
                <td>{{ $product['level3']['name'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>
</html>