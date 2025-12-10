<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineGroup extends Model
{
    protected $table = 'machine_groups';
    protected $primaryKey = 'machine_group_id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'description'
    ];

    /* ACCESSORS & MUTATORS */
    protected function getNameAttribute($value)
    {
        return strtoupper($value);
    }

    protected function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower($value);
    }

    /* RELATIONSHIPS */
    public function productionMachineGroups()
    {
        return $this->hasMany(ProductionMachineGroup::class, 'machine_group_id', 'machine_group_id');
    }
}
