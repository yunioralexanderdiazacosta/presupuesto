<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Facturas</title>
    <link href="{{  public_path('assets/css/table.css') }}" rel="stylesheet" type="text/css">
</head>
<body>
    
    <h2 style="text-align: center;">Facturas</h2>
    <div class="margin-top">
        <table class="table table-bordered">
            <tr align="left">
                <th>Proveedor</th>
                <th>Número de Documento</th>
                <th>Razón Social</th>
                <th>Fecha</th>
                <th>Fecha Vencimiento</th>
                <th>Total</th>
            </tr>
             @foreach($invoices as $invoice)
            <tr class="items">
                <td>{{ $invoice['supplier']['name'] }}</td>
                <td>{{ $invoice['number_document'] }}</td>
                <td>{{ $invoice['companyReason']['name'] }}</td>
                <td>{{ $invoice['date'] }}</td>
                <td>{{ $invoice['due_date'] }}</td>
                <td>{{ $invoice['total'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>
</html>