<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class HourlyLog extends Model
{
    use HasFactory;

    protected $table = 'hourly_logs';

    protected $primaryKey = 'hourly_log_id';

    public $timestamps = true;

    protected $fillable = [
        'production_machine_group_id',
        'recorded_at',
        'output_qty_normal',
        'output_qty_reject',
        'output_grades',
        'output_grade',
        'output_ukuran',
        'keterangan',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'output_grades' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->modified_by = auth()->id();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

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

        // Store timestamps in the DB as Asia/Jakarta (UTC+7) to match existing data.
        $dt = Carbon::parse($value, 'Asia/Jakarta')->setTimezone('Asia/Jakarta');
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

        // Parse stored DB value as Asia/Jakarta so the frontend receives UTC+7.
        return Carbon::parse($value, 'Asia/Jakarta')->setTimezone('Asia/Jakarta');
    }

    /**
     * Calculate total output value by summing all output fields.
     */
    public function getTotalOutputAttribute(): int
    {
        $total = 0;

        if ($this->output_qty_normal !== null) {
            $total += (int) $this->output_qty_normal;
        }

        if ($this->output_qty_reject !== null) {
            $total += (int) $this->output_qty_reject;
        }

        if ($this->output_grades !== null && is_array($this->output_grades)) {
            foreach ($this->output_grades as $gradeValue) {
                $total += (int) ($gradeValue ?? 0);
            }
        }

        return $total;
    }
}
