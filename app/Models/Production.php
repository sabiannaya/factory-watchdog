<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $table = 'productions';
    protected $primaryKey = 'production_id';
    public $timestamps = true;

    protected $fillable = [
        'production_name',
        'status',
    ];

    /* RELATIONSHIPS */
    public function productionMachineGroups()
    {
        return $this->hasMany(ProductionMachineGroup::class, 'production_id', 'production_id');
    }
}
