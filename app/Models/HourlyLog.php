<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class HourlyLog extends Model
{
    use HasFactory;
    protected $table = 'hourly_logs';
    protected $primaryKey = 'hourly_log_id';
    public $timestamps = true;

    protected $fillable = [
        'production_machine_group_id',
        'machine_index',
        'recorded_at',
        'output_value',
        'target_value',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function productionMachineGroup()
    {
        return $this->belongsTo(ProductionMachineGroup::class, 'production_machine_group_id', 'production_machine_group_id');
    }

    /**
     * Mutator: accept a datetime in Asia/Jakarta (UTC+7) and store as UTC.
     */
    protected function setRecordedAtAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['recorded_at'] = null;
            return;
        }

        $dt = Carbon::parse($value, 'Asia/Jakarta')->setTimezone('UTC');
        $this->attributes['recorded_at'] = $dt->toDateTimeString();
    }

    /**
     * Accessor: return recorded_at as Carbon in Asia/Jakarta (UTC+7).
     */
    protected function getRecordedAtAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value, 'UTC')->setTimezone('Asia/Jakarta');
    }
}
