<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMachineGroup extends Model
{
    use HasFactory;

    protected $table = 'production_machine_groups';

    protected $primaryKey = 'production_machine_group_id';

    public $timestamps = true;

    protected $fillable = [
        'production_id',
        'machine_group_id',
        'machine_count',
        'default_target',
        'default_targets',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'default_targets' => 'array',
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
    public function production()
    {
        return $this->belongsTo(Production::class, 'production_id', 'production_id');
    }

    public function machineGroup()
    {
        return $this->belongsTo(MachineGroup::class, 'machine_group_id', 'machine_group_id');
    }

    public function productionMachineGroupTargets()
    {
        return $this->hasMany(ProductionMachineGroupTarget::class, 'production_machine_group_id', 'production_machine_group_id');
    }

    public function dailyTargetValues()
    {
        return $this->hasMany(DailyTargetValue::class, 'production_machine_group_id', 'production_machine_group_id');
    }
}
