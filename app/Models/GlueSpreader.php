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
        // English attribute names (database / API)
        'glue_kg',
        'hardener_kg',
        'powder_kg',
        'colorant_kg',
        'anti_termite_kg',
        'viscosity',
        'washes_per_day',
        'glue_loss_kg',
        'notes',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        // numeric casts for measurements
        'glue_kg' => 'float',
        'hardener_kg' => 'float',
        'powder_kg' => 'float',
        'colorant_kg' => 'float',
        'anti_termite_kg' => 'float',
        'glue_loss_kg' => 'float',
        'washes_per_day' => 'integer',
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
