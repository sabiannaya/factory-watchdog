<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductionMachineGroup extends Model
{
    use HasFactory;
    protected $table = 'production_machine_groups';
    protected $primaryKey = 'production_machine_group_id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'production_id',
        'machine_group_id',
        'machine_count',
        'default_target',
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
    public function production()
    {
        return $this->belongsTo(Production::class, 'production_id', 'production_id');
    }
    public function machineGroup()
    {
        return $this->belongsTo(MachineGroup::class, 'machine_group_id', 'machine_group_id');
    }
}
