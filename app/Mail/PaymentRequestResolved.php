<?php

namespace App\Mail;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRequestResolved extends Mailable
{
    use Queueable, SerializesModels;

    public $paymentRequest;
    public $resolvedByName;

    public function __construct(PaymentRequest $paymentRequest, $resolvedByName)
    {
        $this->paymentRequest = $paymentRequest;
        $this->resolvedByName = $resolvedByName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de Pago Gestionada - ' . $this->paymentRequest->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-request-resolved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
