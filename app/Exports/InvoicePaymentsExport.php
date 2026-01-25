<?php

namespace App\Exports;

use App\Models\InvoicePayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Facades\Auth;

class InvoicePaymentsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $team_id;
    protected $season_id;

    public function __construct($team_id, $season_id)
    {
        $this->team_id = $team_id;
        $this->season_id = $season_id;
    }

    public function collection()
    {
        return InvoicePayment::with(['invoice.supplier', 'invoice.typeDocument', 'bank', 'user'])
            ->where('team_id', $this->team_id)
            ->where('season_id', $this->season_id)
            ->latest('payment_date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha Pago',
            'Número Factura',
            'Proveedor',
            'Tipo Documento',
            'Monto',
            'Método Pago',
            'Banco',
            'Nro. Transacción',
            'Observaciones',
            'Registrado por',
            'Fecha Registro'
        ];
    }

    public function map($payment): array
    {
        $paymentMethods = [
            1 => 'Transferencia',
            2 => 'Efectivo',
            3 => 'Cheque'
        ];

        return [
            $payment->id,
            $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : '',
            $payment->invoice->number_document ?? '',
            $payment->invoice->supplier->name ?? '',
            $payment->invoice->typeDocument->name ?? '',
            $payment->amount, // Exportar número puro para Excel
            $paymentMethods[$payment->payment_method] ?? '',
            $payment->bank->name ?? '',
            $payment->transaction_number ?? '',
            $payment->observations ?? '',
            $payment->user->name ?? '',
            $payment->created_at ? $payment->created_at->format('d-m-Y H:i') : ''
        ];
    }

    public function title(): string
    {
        return 'Pagos de Facturas';
    }
}
