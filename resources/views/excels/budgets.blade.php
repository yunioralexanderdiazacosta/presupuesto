<table>
    <thead>
        <tr>
            <th><b>Nombre</b></th>
        </tr>
    </thead>
    <tbody>
     @foreach($budgets as $budget)
        <tr class="items">
            <td>
                {{ $budget['name'] }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>