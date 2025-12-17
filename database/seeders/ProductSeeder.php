<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a handful of realistic products for development/testing
        Product::factory()->count(20)->create();

        // Add a couple of named products used commonly in UI examples
        Product::factory()->create([
            'name' => 'Standard Board 3mm',
            'thickness' => '3mm',
            'ply' => '2',
            'glue_type' => 'PVA',
            'qty' => 500,
        ]);

        Product::factory()->create([
            'name' => 'Heavy Duty 5mm',
            'thickness' => '5mm',
            'ply' => '3',
            'glue_type' => 'PU',
            'qty' => 200,
        ]);
    }
}
