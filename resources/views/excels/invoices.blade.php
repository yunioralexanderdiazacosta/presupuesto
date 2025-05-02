<table>
    <thead>
        <tr>
            <th><b>Proveedor</b></th>
            <th><b>Número de Documento</b></th>
            <th><b>Razón Social</b></th>
            <th><b>Fecha</b></th>
            <th><b>Fecha Vencimiento</b></th>
            <th><b>Total</b></th>
        </tr>    
    </thead>
    <tbody>
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
    </tbody>
    
</table>