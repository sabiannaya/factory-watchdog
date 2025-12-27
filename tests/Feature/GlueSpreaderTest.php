<?php

use App\Models\GlueSpreader;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GlueSpreader Authorization', function () {
    beforeEach(function () {
        $this->super = User::factory()->create(['role_id' => Role::whereSlug(Role::SUPER)->first()->role_id]);
        $this->staff = User::factory()->create(['role_id' => Role::whereSlug(Role::STAFF)->first()->role_id]);
        $this->staffWithAccess = User::factory()->create([
            'role_id' => Role::whereSlug(Role::STAFF)->first()->role_id,
            'can_access_glue_spreaders' => true,
        ]);
        $this->glueSpreader = GlueSpreader::factory()->create(['created_by' => $this->super->id]);
    });

    test('super can view glue spreader menu', function () {
        $this->actingAs($this->super);
        expect($this->super->canAccessGlueSpreaders())->toBeTrue();
    });

    test('staff without access cannot view glue spreader menu', function () {
        $this->actingAs($this->staff);
        expect($this->staff->canAccessGlueSpreaders())->toBeFalse();
    });

    test('staff with access can view glue spreader menu', function () {
        $this->actingAs($this->staffWithAccess);
        expect($this->staffWithAccess->canAccessGlueSpreaders())->toBeTrue();
    });

    test('staff with access can view any glue spreader', function () {
        $this->actingAs($this->staffWithAccess);
        $this->get(route('data-management.glue-spreaders.show', $this->glueSpreader->glue_spreader_id))->assertSuccessful();
    });

    test('staff without access cannot view glue spreader', function () {
        $this->actingAs($this->staff);
        $this->get(route('data-management.glue-spreaders.show', $this->glueSpreader->glue_spreader_id))->assertForbidden();
    });

    test('staff without access cannot create glue spreader', function () {
        $this->actingAs($this->staff);
        $this->get(route('data-management.glue-spreaders.create'))->assertForbidden();
    });

    test('staff with access can create glue spreader', function () {
        $this->actingAs($this->staffWithAccess);
        $this->get(route('data-management.glue-spreaders.create'))->assertSuccessful();
    });

    test('super can create glue spreader', function () {
        $this->actingAs($this->super);
        $this->get(route('data-management.glue-spreaders.create'))->assertSuccessful();
    });
});

describe('GlueSpreader Soft Delete', function () {
    beforeEach(function () {
        $this->super = User::factory()->create(['role_id' => Role::whereSlug(Role::SUPER)->first()->role_id]);
        $this->staffWithAccess = User::factory()->create([
            'role_id' => Role::whereSlug(Role::STAFF)->first()->role_id,
            'can_access_glue_spreaders' => true,
        ]);
        $this->glueSpreader = GlueSpreader::factory()->create(['created_by' => $this->super->id]);
    });

    test('staff with access can soft delete any glue spreader', function () {
        $this->actingAs($this->staffWithAccess);
        $this->delete(route('data-management.glue-spreaders.destroy', $this->glueSpreader->glue_spreader_id))
            ->assertRedirect(route('data-management.glue-spreaders.index'));

        expect(GlueSpreader::withTrashed()->find($this->glueSpreader->glue_spreader_id)->deleted_at)->not->toBeNull();
        expect(GlueSpreader::withTrashed()->find($this->glueSpreader->glue_spreader_id)->deleted_by)->toBe($this->staffWithAccess->id);
    });

    test('staff without access cannot soft delete glue spreader', function () {
        $staffWithoutAccess = User::factory()->create(['role_id' => Role::whereSlug(Role::STAFF)->first()->role_id]);

        $this->actingAs($staffWithoutAccess);
        $this->delete(route('data-management.glue-spreaders.destroy', $this->glueSpreader->glue_spreader_id))->assertForbidden();
    });

    test('super can soft delete any glue spreader', function () {
        $this->actingAs($this->super);
        $this->delete(route('data-management.glue-spreaders.destroy', $this->glueSpreader->glue_spreader_id))
            ->assertRedirect(route('data-management.glue-spreaders.index'));

        expect(GlueSpreader::withTrashed()->find($this->glueSpreader->glue_spreader_id)->deleted_at)->not->toBeNull();
    });
});

describe('GlueSpreader Hard Delete', function () {
    beforeEach(function () {
        $this->super = User::factory()->create(['role_id' => Role::whereSlug(Role::SUPER)->first()->role_id]);
        $this->staffWithAccess = User::factory()->create([
            'role_id' => Role::whereSlug(Role::STAFF)->first()->role_id,
            'can_access_glue_spreaders' => true,
        ]);
        $this->glueSpreader = GlueSpreader::factory()->create(['created_by' => $this->super->id]);
    });

    test('staff cannot force delete glue spreader', function () {
        $this->actingAs($this->staffWithAccess);
        $this->delete(route('data-management.glue-spreaders.force-delete', $this->glueSpreader->glue_spreader_id))->assertForbidden();
    });

    test('super can force delete glue spreader', function () {
        $this->glueSpreader->delete();

        $this->actingAs($this->super);
        $this->delete(route('data-management.glue-spreaders.force-delete', $this->glueSpreader->glue_spreader_id))
            ->assertRedirect(route('data-management.glue-spreaders.index'));

        expect(GlueSpreader::withTrashed()->find($this->glueSpreader->glue_spreader_id))->toBeNull();
    });
});

describe('GlueSpreader Tracking', function () {
    beforeEach(function () {
        $this->super = User::factory()->create(['role_id' => Role::whereSlug(Role::SUPER)->first()->role_id]);
    });

    test('created_by is set automatically when creating glue spreader', function () {
        $this->actingAs($this->super);

        $glueSpreader = GlueSpreader::factory()->create(['created_by' => null]);
        $glueSpreader->update(['name' => 'New Name']);

        expect($glueSpreader->refresh()->created_by)->toBe($glueSpreader->id);
    });

    test('modified_by is updated when modifying glue spreader', function () {
        $glueSpreader = GlueSpreader::factory()->create(['created_by' => $this->super->id]);
        $originalModifiedBy = $glueSpreader->modified_by;

        $this->actingAs($this->super);
        $glueSpreader->update(['name' => 'Updated Name']);

        expect($glueSpreader->refresh()->modified_by)->toBe($this->super->id);
    });

    test('deleted_by is set when soft deleting', function () {
        $this->actingAs($this->super);
        $glueSpreader = GlueSpreader::factory()->create(['created_by' => $this->super->id]);

        $this->delete(route('data-management.glue-spreaders.destroy', $glueSpreader->glue_spreader_id));

        expect($glueSpreader->refresh()->deleted_by)->toBe($this->super->id);
    });
});

describe('GlueSpreader Index Filtering', function () {
    beforeEach(function () {
        $this->super = User::factory()->create(['role_id' => Role::whereSlug(Role::SUPER)->first()->role_id]);
        $this->staffWithAccess = User::factory()->create([
            'role_id' => Role::whereSlug(Role::STAFF)->first()->role_id,
            'can_access_glue_spreaders' => true,
        ]);

        $this->spreader1 = GlueSpreader::factory()->create(['name' => 'Spreader One', 'created_by' => $this->super->id]);
        $this->spreader2 = GlueSpreader::factory()->create(['name' => 'Spreader Two', 'created_by' => $this->super->id]);
    });

    test('super sees all glue spreaders', function () {
        $this->actingAs($this->super);
        $response = $this->get(route('data-management.glue-spreaders.index'));

        expect($response['glueSpreaders']['data'])->toHaveCount(2);
    });

    test('staff with access sees all glue spreaders', function () {
        $this->actingAs($this->staffWithAccess);
        $response = $this->get(route('data-management.glue-spreaders.index'));

        expect($response['glueSpreaders']['data'])->toHaveCount(2);
    });

    test('staff without access cannot view index', function () {
        $staffWithoutAccess = User::factory()->create(['role_id' => Role::whereSlug(Role::STAFF)->first()->role_id]);
        $this->actingAs($staffWithoutAccess);

        $this->get(route('data-management.glue-spreaders.index'))->assertForbidden();
    });
});
