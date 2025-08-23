<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_product_id',
        'user_id',
        'project_id',
        'operation_id',
        'machinery_id',
        'quantity',
        'notes',
        'date',
        'team_id',
        'season_id',
    ];

    // Relaciones
    public function invoiceProduct()
    {
        return $this->belongsTo(InvoiceProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class);
    }

    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function costCenters()
    {
        return $this->hasMany(OutflowCostCenter::class);
    }
}
