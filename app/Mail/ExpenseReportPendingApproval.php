<?php

namespace App\Mail;

use App\Models\ExpenseReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ExpenseReportPendingApproval extends Mailable
{
    use Queueable, SerializesModels;

    public $expenseReport;
    public $approveUrl;
    public $rejectUrl;
    public $approverName;

    public function __construct(ExpenseReport $expenseReport, $approverName = null)
    {
        $this->expenseReport = $expenseReport;
        $this->approverName = $approverName ?? 'Aprobador';

        $this->approveUrl = URL::temporarySignedRoute(
            'expense-reports.approve',
            now()->addHours(48),
            ['expenseReport' => $expenseReport->id]
        );

        $this->rejectUrl = URL::temporarySignedRoute(
            'expense-reports.reject',
            now()->addHours(48),
            ['expenseReport' => $expenseReport->id]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rendición Pendiente de Aprobación - ' . $this->expenseReport->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.expense-report-pending-approval',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
