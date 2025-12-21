<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlueSpreader extends Model
{
    use HasFactory;

    protected $primaryKey = 'glue_spreader_id';

    protected $fillable = [
        'name',
        'model',
        'capacity_ml',
        'speed_mpm',
        'status',
        'notes',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'capacity_ml' => 'integer',
        'speed_mpm' => 'integer',
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
}
