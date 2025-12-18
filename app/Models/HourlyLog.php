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
        'machine_index',
        'recorded_at',
        'output_value',
        'target_value',
        'qty',
        'qty_normal',
        'qty_reject',
        'grades',
        'grade',
        'ukuran',
        'keterangan',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'grades' => 'array',
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

    /**
     * Calculate output_value from flexible input fields.
     * This aggregates all quantity-related fields into a single output_value.
     */
    public function calculateOutputValue(): int
    {
        $total = 0;

        // Simple qty
        if ($this->qty !== null) {
            $total += (int) $this->qty;
        }

        // Normal and reject quantities
        if ($this->qty_normal !== null) {
            $total += (int) $this->qty_normal;
        }
        if ($this->qty_reject !== null) {
            $total += (int) $this->qty_reject;
        }

        // Grades (sum all grade values)
        if ($this->grades !== null && is_array($this->grades)) {
            foreach ($this->grades as $gradeValue) {
                $total += (int) ($gradeValue ?? 0);
            }
        }

        // Grade with qty (for Film type - qty is separate field)
        // Note: grade field itself is just a label, qty is the actual quantity

        // For CNC/DS2, qty is already included above

        return $total;
    }

    /**
     * Boot method to auto-calculate output_value before saving.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $log) {
            // Only auto-calculate if output_value is not explicitly set
            // or if it's 0 and we have other input fields
            if ($log->output_value === null || ($log->output_value === 0 && $log->hasInputData())) {
                $log->output_value = $log->calculateOutputValue();
            }
        });
    }

    /**
     * Check if this log has any input data.
     */
    public function hasInputData(): bool
    {
        return $this->qty !== null
            || $this->qty_normal !== null
            || $this->qty_reject !== null
            || ($this->grades !== null && ! empty($this->grades))
            || $this->grade !== null
            || $this->ukuran !== null;
    }
}
