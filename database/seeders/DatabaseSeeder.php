<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call(RoleSeeder::class);

        // Create test user with Super role
        $superRole = Role::where('slug', Role::SUPER)->first();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => $superRole?->role_id,
        ]);

        // $this->call([
        //     ProductionSeeder::class,
        //     ProductSeeder::class,
        //     GlueSpreaderSeeder::class,
        // ]);
    }
}
