<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $purchaseOrder;
    public $rejectedBy;
    public $rejectedByName;
    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, $rejectionReason = null, $rejectedByName = null)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->rejectedBy = $purchaseOrder->approvedBy; // El campo se reutiliza para registrar quién rechazó
        $this->rejectedByName = $rejectedByName ?? $this->rejectedBy?->name ?? 'Sistema';
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Orden de Compra Rechazada - ' . $this->purchaseOrder->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-rejected',
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
