<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nivel 2</title>
    <link href="{{  public_path('assets/css/table.css') }}" rel="stylesheet" type="text/css">
</head>
<body>
    <h2 style="text-align: center;">Nivel 2</h2>
    <div class="margin-top">
        <table class="table table-bordered">
            <tr align="left">
                <th>Nombre</th>
                <th>Nivel 1</th>
            </tr>
            @foreach($levels as $level)
            <tr class="items">
                <td>{{ $level['name'] }}</td>
                <td>{{ $level['level1']['name'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>
</html>