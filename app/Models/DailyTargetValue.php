<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class DailyTargetValue extends Model
{
    use HasFactory;

    protected $table = 'daily_target_values';
    protected $primaryKey = 'daily_target_value_id';
    public $timestamps = true;

    protected $fillable = [
        'production_machine_group_id',
        'date',
        'field_name',
        'target_value',
        'actual_value',
        'keterangan',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Mutator: accept a date in Asia/Jakarta (UTC+7) and store as Y-m-d.
     */
    protected function setDateAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['date'] = null;
            return;
        }

        $dt = Carbon::parse($value, 'Asia/Jakarta');
        $this->attributes['date'] = $dt->toDateString();
    }

    /**
     * Accessor: return date as Carbon at Asia/Jakarta timezone.
     */
    protected function getDateAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $value, 'UTC')->setTimezone('Asia/Jakarta');
    }

    /* RELATIONSHIPS */
    public function productionMachineGroup()
    {
        return $this->belongsTo(ProductionMachineGroup::class, 'production_machine_group_id', 'production_machine_group_id');
    }
}

