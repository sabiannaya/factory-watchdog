<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserProduction extends Pivot
{
    protected $table = 'user_productions';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'production_id',
    ];
}
