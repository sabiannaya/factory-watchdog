<?php

namespace Database\Seeders;

use App\Models\GlueSpreader;
use Illuminate\Database\Seeder;

class GlueSpreaderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GlueSpreader::factory()->count(5)->create();
    }
}
