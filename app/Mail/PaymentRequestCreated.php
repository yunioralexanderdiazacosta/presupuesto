<?php

namespace App\Mail;

use App\Models\PaymentRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PaymentRequestCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $paymentRequest;
    public $recipientName;
    public $resolveUrl;

    public function __construct(PaymentRequest $paymentRequest, User $recipient)
    {
        $this->paymentRequest = $paymentRequest;
        $this->recipientName = $recipient->name;

        $this->resolveUrl = URL::temporarySignedRoute(
            'payment-requests.resolve',
            now()->addDays(7),
            ['paymentRequest' => $paymentRequest->id, 'user' => $recipient->id]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de Pago ' . $this->paymentRequest->number . ' (' . $this->paymentRequest->character_label . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-request-created',
        );
    }

    public function attachments(): array
    {
        $attachments = [
            Attachment::fromData(
                fn () => Pdf::loadView('pdfs.payment-request', ['paymentRequest' => $this->paymentRequest])->output(),
                'solicitud-' . $this->paymentRequest->number . '.pdf'
            )->withMime('application/pdf'),
        ];

        foreach ($this->paymentRequest->files as $file) {
            $attachments[] = Attachment::fromStorageDisk('public', $file->file_path)->as($file->original_name ?? basename($file->file_path));
        }

        return $attachments;
    }
}
