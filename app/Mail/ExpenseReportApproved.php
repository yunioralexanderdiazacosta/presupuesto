<?php

namespace App\Mail;

use App\Models\ExpenseReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpenseReportApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $expenseReport;
    public $approverName;

    public function __construct(ExpenseReport $expenseReport, $approverName = null)
    {
        $this->expenseReport = $expenseReport;
        $this->approverName = $approverName ?? 'Administración';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rendición Aprobada - ' . $this->expenseReport->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.expense-report-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
