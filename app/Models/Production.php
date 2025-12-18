<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Production extends Model
{
    use HasFactory;

    protected $table = 'productions';

    protected $primaryKey = 'production_id';

    public $timestamps = true;

    protected $fillable = [
        'production_name',
        'status',
        'created_by',
        'modified_by',
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

    /* RELATIONSHIPS */
    public function productionMachineGroups()
    {
        return $this->hasMany(ProductionMachineGroup::class, 'production_id', 'production_id');
    }
}
