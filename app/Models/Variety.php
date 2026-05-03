<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variety extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'fruit_id', 'team_id', 'observations'];

    public function fruit()
    {
        return $this->belongsTo(Fruit::class, 'fruit_id');
    }

    public function costCenters()
    {
        return $this->hasMany(CostCenter::class);
    }

    public function costCenterVarieties()
    {
        return $this->hasMany(CostCenterVariety::class);
    }

    public function projectEvaluationRows()
    {
        return $this->hasMany(ProjectEvaluationRow::class);
    }

    public function rnpPrices()
    {
        return $this->hasMany(RnpPrice::class);
    }

    public function varietyCostParams()
    {
        return $this->hasMany(VarietyCostParam::class);
    }

    public function productionSummaries()
    {
        return $this->hasMany(ProductionSummary::class);
    }

    public function hasAssociatedRecords(): bool
    {
        return $this->costCenters()->exists()
            || $this->costCenterVarieties()->exists()
            || $this->projectEvaluationRows()->exists()
            || $this->rnpPrices()->exists()
            || $this->varietyCostParams()->exists()
            || $this->productionSummaries()->exists();
    }
}
