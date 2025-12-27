<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'warehouse_id';

    protected $fillable = [
        'source',
        'quantity',
        'packing',
        'notes',
        'is_active',
        'created_by',
        'modified_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by', 'id');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->modified_by = auth()->id();
            }
        });
    }
}
