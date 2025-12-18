<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DailyTarget extends Model
{
    use HasFactory;

    protected $table = 'daily_targets';

    protected $primaryKey = 'daily_target_id';

    public $timestamps = true;

    protected $fillable = [
        'date',
        'target_value',
        'actual_value',
        'notes',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'date' => 'date',
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
    public function dailyTargetValues()
    {
        return $this->hasManyThrough(
            DailyTargetValue::class,
            Production::class,
            'production_id',
            'production_machine_group_id'
        );
    }
}
