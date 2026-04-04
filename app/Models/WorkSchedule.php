<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'season_id',
        'monday_hours',
        'tuesday_hours',
        'wednesday_hours',
        'thursday_hours',
        'friday_hours',
        'saturday_hours',
        'sunday_hours',
        'weekly_hours',
    ];

    protected $casts = [
        'monday_hours' => 'decimal:1',
        'tuesday_hours' => 'decimal:1',
        'wednesday_hours' => 'decimal:1',
        'thursday_hours' => 'decimal:1',
        'friday_hours' => 'decimal:1',
        'saturday_hours' => 'decimal:1',
        'sunday_hours' => 'decimal:1',
        'weekly_hours' => 'decimal:1',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Obtener las horas para un día de la semana (1=lunes ... 7=domingo)
     */
    public function hoursForDayOfWeek(int $dayOfWeek): float
    {
        $map = [
            1 => 'monday_hours',
            2 => 'tuesday_hours',
            3 => 'wednesday_hours',
            4 => 'thursday_hours',
            5 => 'friday_hours',
            6 => 'saturday_hours',
            7 => 'sunday_hours',
        ];

        $field = $map[$dayOfWeek] ?? 'monday_hours';
        return (float) $this->{$field};
    }
}
