<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestFile extends Model
{
    protected $fillable = [
        'payment_request_id',
        'file_path',
        'original_name',
    ];

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class);
    }
}
