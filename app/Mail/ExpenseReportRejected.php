<?php

namespace App\Mail;

use App\Models\ExpenseReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpenseReportRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $expenseReport;
    public $rejectionReason;
    public $rejectedByName;

    public function __construct(ExpenseReport $expenseReport, $rejectionReason = null, $rejectedByName = null)
    {
        $this->expenseReport = $expenseReport;
        $this->rejectionReason = $rejectionReason;
        $this->rejectedByName = $rejectedByName ?? 'Administración';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rendición Rechazada - ' . $this->expenseReport->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.expense-report-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
