<table>
    <thead>
        <tr>
            <th colspan="9"><b>Detalle de Tarjas - {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}</b></th>
        </tr>
        <tr>
            <th><b>Colaborador</b></th>
            <th><b>Fecha</b></th>
            <th><b>Tipo</b></th>
            <th><b>Labor</b></th>
            <th><b>Trato</b></th>
            <th><b>Tarifa</b></th>
            <th><b>Cant.</b></th>
            <th><b>Monto</b></th>
            <th><b>JH</b></th>
            <th><b>Bono</b></th>
            <th><b>C.Costo</b></th>
        </tr>
    </thead>
    <tbody>
        @forelse($employees as $emp)
            @php $firstRow = true; @endphp
            @foreach($dates as $date)
                @php $dayData = $emp['days'][$date]; @endphp
                @if($dayData['lines']->isNotEmpty())
                    @foreach($dayData['lines'] as $line)
                    <tr>
                        <td>{{ $firstRow ? $emp['full_name'] . ' (' . $emp['rut'] . ')' : '' }}</td>
                        <td>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</td>
                        <td>{{ ($line->payment_type ?? 'trato') === 'dia' ? 'Al día' : 'A trato' }}</td>
                        <td>{{ $line->laborType?->name }}</td>
                        <td>{{ $line->laborRate?->name ?? '-' }}</td>
                        <td>{{ $line->rate }}</td>
                        <td>{{ $line->quantity }}</td>
                        <td>{{ $line->amount }}</td>
                        <td>{{ $line->workdays }}</td>
                        <td>{{ $line->bonus_amount ?: '' }}</td>
                        <td>{{ $line->costCenter?->name }}</td>
                    </tr>
                    @php $firstRow = false; @endphp
                    @endforeach
                @endif
            @endforeach
            <tr>
                <td><b>TOTAL {{ $emp['full_name'] }}</b></td>
                <td></td><td></td><td></td><td></td><td></td><td></td>
                <td><b>{{ $emp['grand_total_amount'] }}</b></td>
                <td><b>{{ $emp['grand_total_workdays'] }}</b></td>
                <td><b>{{ $emp['grand_total_bonus'] }}</b></td>
                <td></td>
            </tr>
        @empty
        <tr>
            <td colspan="11">Sin datos para el período seleccionado</td>
        </tr>
        @endforelse
    </tbody>
</table>
