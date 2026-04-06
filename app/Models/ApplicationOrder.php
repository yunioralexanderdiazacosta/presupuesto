<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'start_date',
        'volume',
        'mojamiento',
        'recomendado',
        'status',
        'responsable',
        'observations',
        'team_id',
        'season_id',
        'phenological_stage_id',
        'tractors',
        'equipments',
        'operators',
    ];

    protected $casts = [
        'date' => 'date',
        'start_date' => 'date',
        'mojamiento' => 'decimal:2',
        'volume' => 'integer',
    ];

    /**
     * Relación con Team
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación con Season
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Relación con PhenologicalStage
     */
    public function phenologicalStage()
    {
        return $this->belongsTo(PhenologicalStage::class);
    }

    /**
     * Relación muchos a muchos con Products
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'application_order_product')
            ->withPivot([
                'tipo_dosis',
                'dosis_por_100',
                'dosis_por_hectarea',
                'cantidad_por_hectarea',
                'cantidad_total',
                'carencia',
                'reingreso'
            ])
            ->withTimestamps();
    }

    /**
     * Relación muchos a muchos con CostCenters
     */
    public function costCenters()
    {
        return $this->belongsToMany(CostCenter::class, 'application_order_cost_center')
            ->withTimestamps();
    }

    /**
     * Relación con ApplicationOrderProduct (modelo pivot personalizado)
     */
    public function orderProducts()
    {
        return $this->hasMany(ApplicationOrderProduct::class);
    }

    /**
     * Relación con ApplicationOrderCostCenter (modelo pivot personalizado)
     */
    public function orderCostCenters()
    {
        return $this->hasMany(ApplicationOrderCostCenter::class);
    }
}
