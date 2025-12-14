<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductionMachineGroupTarget extends Model
{
    use HasFactory;
    protected $table = 'production_machine_group_targets';
    protected $primaryKey = 'production_machine_group_target_id';
    public $timestamps = true;

    protected $fillable = [
        'production_machine_group_id',
        'daily_target_id',
    ];

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
