<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'supplier_id',
        'company_reason_id',
        'season_id',
        'team_id',
        'status',
        'requested_by',
        'approved_by',
        'assigned_to',
        'order_date',
        'delivery_date',
        'payment_terms',
        'subtotal',
        'tax',
        'total',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected $appends = ['status_label', 'status_color'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function companyReason()
    {
        return $this->belongsTo(CompanyReason::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function costCenters()
    {
        return $this->belongsToMany(CostCenter::class, 'purchase_order_cost_center');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'purchase_order_items')
            ->withPivot(['id', 'quantity', 'unit_id', 'unit_price', 'subtotal', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'draft' => 'secondary',
            'pending' => 'warning',
            'approved' => 'info',
            'rejected' => 'danger',
            'sent' => 'primary',
            'received_partial' => 'warning',
            'completed' => 'success',
            'cancelled' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'draft' => 'Borrador',
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'sent' => 'Enviada',
            'received_partial' => 'Recibida Parcial',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => 'Desconocido',
        };
    }
}
