<table>
    <thead>
        <tr>
            <th><b>Colaborador</b></th>
            <th><b>RUT</b></th>
            @foreach($dates as $date)
                <th><b>{{ \Carbon\Carbon::parse($date)->format('d') }}</b></th>
            @endforeach
            <th><b>Total $</b></th>
            <th><b>Total Bono</b></th>
            <th><b>Total JH</b></th>
        </tr>
    </thead>
    <tbody>
        @foreach($employees as $emp)
        <tr>
            <td>{{ $emp['full_name'] }}</td>
            <td>{{ $emp['rut'] }}</td>
            @foreach($dates as $date)
                <td>{{ $emp['days'][$date]['amount'] ?: '' }}</td>
            @endforeach
            <td>{{ $emp['grand_total_amount'] }}</td>
            <td>{{ $emp['grand_total_bonus'] }}</td>
            <td>{{ $emp['grand_total_workdays'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
