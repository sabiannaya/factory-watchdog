<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $primaryKey = 'role_id';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public const SUPER = 'super';

    public const STAFF = 'staff';

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }

    public function isSuper(): bool
    {
        return $this->slug === self::SUPER;
    }

    public function isStaff(): bool
    {
        return $this->slug === self::STAFF;
    }
}
