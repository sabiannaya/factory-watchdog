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
        // Create some explicit sample records using the new English attribute names
        GlueSpreader::create([
            'name' => 'Main Glue Spreader',
            'model' => 'GS-100',
            'glue_kg' => 120.50,
            'hardener_kg' => 10.25,
            'powder_kg' => 50.00,
            'colorant_kg' => 2.5,
            'anti_termite_kg' => 0.0,
            'viscosity' => 'medium',
            'washes_per_day' => 3,
            'glue_loss_kg' => 1.2,
            'notes' => 'Primary line glue spreader sample',
            'created_by' => 1,
        ]);

        GlueSpreader::create([
            'name' => 'Backup Glue Spreader',
            'model' => 'GS-200',
            'glue_kg' => 75.00,
            'hardener_kg' => 5.0,
            'powder_kg' => 20.0,
            'colorant_kg' => 1.0,
            'anti_termite_kg' => 0.5,
            'viscosity' => 'low',
            'washes_per_day' => 1,
            'glue_loss_kg' => 0.5,
            'notes' => 'Backup unit near packing',
            'created_by' => 1,
        ]);

        // Additional random entries via factory for more test data
        GlueSpreader::factory()->count(3)->create();
    }
}
