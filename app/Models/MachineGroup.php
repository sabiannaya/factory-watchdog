<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MachineGroup extends Model
{
    use HasFactory;
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
        return ucfirst(strtolower($value));
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
