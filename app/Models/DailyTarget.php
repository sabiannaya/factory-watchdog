<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}
