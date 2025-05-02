   <!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Parcelas</title>
    <link href="{{  public_path('assets/css/table.css') }}" rel="stylesheet" type="text/css">
</head>
<body>
    
    <h2 style="text-align: center;">Parcelas</h2>
    <div class="margin-top">
        <table class="table table-bordered">
            <tr align="left">
                <th>Nombre</th>
                <th>Razón Social</th>
                <th>Temporada</th>
            </tr>
             @foreach($parcels as $parcel)
            <tr class="items">
                <td>{{ $parcel['name'] }}</td>
                <td>{{ $parcel['companyReason']['name'] }}</td>
                <td>{{ $parcel['season']['name'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>
</html>