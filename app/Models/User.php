<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'can_access_glue_spreaders',
        'can_access_warehouse',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'can_access_glue_spreaders' => 'boolean',
            'can_access_warehouse' => 'boolean',
        ];
    }

    /* RELATIONSHIPS */

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function productions(): BelongsToMany
    {
        return $this->belongsToMany(Production::class, 'user_productions', 'user_id', 'production_id')
            ->withTimestamps();
    }

    public function glueSpreaders(): BelongsToMany
    {
        return $this->belongsToMany(GlueSpreader::class, 'user_glue_spreaders', 'user_id', 'glue_spreader_id')
            ->withTimestamps();
    }

    /* ROLE HELPERS */

    public function isSuper(): bool
    {
        return $this->role?->slug === Role::SUPER;
    }

    public function isStaff(): bool
    {
        return $this->role?->slug === Role::STAFF;
    }

    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    /**
     * Check if user can access a specific production.
     * Super users can access all productions.
     * Staff can only access assigned productions.
     */
    public function canAccessProduction(int $productionId): bool
    {
        if ($this->isSuper()) {
            return true;
        }

        return $this->productions()->where('productions.production_id', $productionId)->exists();
    }

    /**
     * Check if user can access a specific machine group.
     * Machine groups are accessible if user can access the production it's attached to.
     */
    public function canAccessMachineGroup(int $machineGroupId): bool
    {
        if ($this->isSuper()) {
            return true;
        }

        $productionMachineGroup = ProductionMachineGroup::where('machine_group_id', $machineGroupId)->first();

        if (! $productionMachineGroup) {
            return false;
        }

        return $this->canAccessProduction($productionMachineGroup->production_id);
    }

    /**
     * Get production IDs that this user can access.
     */
    public function accessibleProductionIds(): array
    {
        if ($this->isSuper()) {
            return Production::pluck('production_id')->toArray();
        }

        return $this->productions()->pluck('productions.production_id')->toArray();
    }

    /**
     * Check if user can delete resources (only Super can delete).
     */
    public function canDelete(): bool
    {
        return $this->isSuper();
    }

    /**
     * Check if user can access glue spreaders.
     * Super users can always access. Staff need the flag enabled.
     */
    public function canAccessGlueSpreaders(): bool
    {
        if ($this->isSuper()) {
            return true;
        }

        return (bool) $this->can_access_glue_spreaders;
    }

    /**
     * Check if user can access warehouse records.
     * Super users can always access. Staff need the flag enabled.
     */
    public function canAccessWarehouse(): bool
    {
        if ($this->isSuper()) {
            return true;
        }

        return $this->can_access_warehouse;
    }
}
