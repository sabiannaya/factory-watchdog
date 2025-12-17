<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $primaryKey = 'product_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'name',
        'thickness',
        'ply',
        'glue_type',
        'qty',
        'notes',
    ];

    /**
     * The attribute type casts.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'qty' => 'integer',
    ];
}
