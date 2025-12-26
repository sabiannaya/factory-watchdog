<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super',
                'slug' => Role::SUPER,
                'description' => 'Super user with full access to all features including deletion of resources.',
            ],
            [
                'name' => 'Staff',
                'slug' => Role::STAFF,
                'description' => 'Staff user who can only access assigned productions and perform create/read/update operations.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
