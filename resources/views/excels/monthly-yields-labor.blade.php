<table>
    <thead>
        <tr>
            <th><b>Labor</b></th>
            <th><b>Nivel 3</b></th>
            <th><b>Nombre</b></th>
            <th><b>Fecha</b></th>
            <th><b>Trato</b></th>
            <th><b>Tarifa</b></th>
            <th><b>Cant.</b></th>
            <th><b>Monto</b></th>
            <th><b>JH</b></th>
            <th><b>Bono</b></th>
            <th><b>P.Obj.</b></th>
        </tr>
    </thead>
    <tbody>
        @forelse($employees as $emp)
            @foreach($dates as $date)
                @php $dayData = $emp['days'][$date]; @endphp
                @if($dayData['lines']->isNotEmpty())
                    @foreach($dayData['lines'] as $line)
                    <tr>
                        <td>{{ $line->laborType?->name ?? '—' }}</td>
                        <td>{{ $line->laborType?->level3?->name ?? '—' }}</td>
                        <td>{{ $emp['full_name'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                        <td>{{ ($line->payment_type ?? 'trato') === 'dia' ? 'Al día' : 'A trato' }}</td>
                        <td>{{ $line->rate }}</td>
                        <td>{{ $line->quantity }}</td>
                        <td>{{ $line->amount }}</td>
                        <td>{{ $line->workdays }}</td>
                        <td>{{ $line->bonus_amount ?: '' }}</td>
                        <td>{{ $line->target_price_bonus ?: '' }}</td>
                    </tr>
                    @endforeach
                @endif
            @endforeach
        @empty
        <tr>
            <td colspan="11">Sin datos para el período seleccionado</td>
        </tr>
        @endforelse
    </tbody>
</table>
