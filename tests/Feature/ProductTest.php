<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('performs product CRUD via endpoints', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $data = Product::factory()->make()->toArray();

    // Create
    $create = $this->postJson('/data-management/products', $data);
    $create->assertCreated()->assertJsonFragment(['name' => $data['name']]);

    $id = $create->json('data.id');

    // Show
    $this->getJson("/data-management/products/{$id}")->assertOk()->assertJsonFragment(['name' => $data['name']]);

    // Update
    $update = ['name' => 'Updated Product Name', 'qty' => 555];
    $this->putJson("/data-management/products/{$id}", $update)->assertOk()->assertJsonFragment(['name' => 'Updated Product Name', 'qty' => 555]);

    // Delete
    $this->deleteJson("/data-management/products/{$id}")->assertNoContent();

    $this->assertDatabaseMissing('products', ['id' => $id]);
});
