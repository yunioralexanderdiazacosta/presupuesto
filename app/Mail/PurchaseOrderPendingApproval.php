<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PurchaseOrderPendingApproval extends Mailable
{
    use Queueable, SerializesModels;

    public $purchaseOrder;
    public $approveUrl;
    public $rejectUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
        
        // Generar URLs firmadas que expiran en 48 horas
        $this->approveUrl = URL::temporarySignedRoute(
            'purchase-orders.approve',
            now()->addHours(48),
            ['purchaseOrder' => $purchaseOrder->id]
        );

        $this->rejectUrl = URL::temporarySignedRoute(
            'purchase-orders.reject',
            now()->addHours(48),
            ['purchaseOrder' => $purchaseOrder->id]
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva Orden de Compra Pendiente de Aprobación - ' . $this->purchaseOrder->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-pending-approval',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
