<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $purchaseOrder;
    public $approvedBy;
    public $approvedByName;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, $approvedByName = null)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->approvedBy = $purchaseOrder->approvedBy;
        $this->approvedByName = $approvedByName ?? $this->approvedBy?->name ?? 'Sistema';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Orden de Compra Aprobada - ' . $this->purchaseOrder->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-approved',
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
