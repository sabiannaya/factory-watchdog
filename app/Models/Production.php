<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Production extends Model
{
    use HasFactory;
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
