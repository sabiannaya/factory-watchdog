<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMachineGroupTarget extends Model
{
    use HasFactory;

    protected $table = 'production_machine_group_targets';

    protected $primaryKey = 'production_machine_group_target_id';

    public $timestamps = true;

    protected $fillable = [
        'production_machine_group_id',
        'daily_target_id',
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
    public function productionMachineGroup()
    {
        return $this->belongsTo(ProductionMachineGroup::class, 'production_machine_group_id', 'production_machine_group_id');
    }

    public function dailyTarget()
    {
        return $this->belongsTo(DailyTarget::class, 'daily_target_id', 'daily_target_id');
    }
}
