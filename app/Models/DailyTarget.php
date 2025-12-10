<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyTarget extends Model
{
    protected $table = 'daily_targets';
    protected $primaryKey = 'daily_target_id';
    public $timestamps = true;

    protected $fillable = [
        'date',
        'target_value',
        'actual_value',
        'notes',
    ];
}
