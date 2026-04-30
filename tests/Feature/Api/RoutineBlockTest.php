<?php

use App\Models\RoutineBlock;
use App\Models\User;

test('guest cannot access routine blocks', function () {
    $this->getJson('/api/morning-hub/routine')->assertUnauthorized();
});

test('user can fetch their routine blocks', function () {
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->create(['sort_order' => 2, 'name' => 'Second']);
    RoutineBlock::factory()->for($user)->create(['sort_order' => 1, 'name' => 'First']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/routine')
        ->assertOk()
        ->assertJsonCount(2, 'blocks')
        ->assertJsonPath('blocks.0.name', 'First');
});

test('user can create a routine block', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/routine/blocks', [
            'type' => 'braindump',
            'name' => 'Morning Dump',
            'timer_minutes' => 10,
        ])
        ->assertCreated()
        ->assertJsonPath('block.name', 'Morning Dump');
});

test('user can update their routine block', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create(['name' => 'Old Name']);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/morning-hub/routine/blocks/{$block->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('block.name', 'New Name');
});

test('user cannot update another user\'s block', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $block = RoutineBlock::factory()->for($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->putJson("/api/morning-hub/routine/blocks/{$block->id}", ['name' => 'Hack'])
        ->assertForbidden();
});

test('user can delete their routine block', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/morning-hub/routine/blocks/{$block->id}")
        ->assertNoContent();

    expect(RoutineBlock::find($block->id))->toBeNull();
});

test('user can reorder routine blocks', function () {
    $user = User::factory()->create();
    $block1 = RoutineBlock::factory()->for($user)->create(['sort_order' => 0]);
    $block2 = RoutineBlock::factory()->for($user)->create(['sort_order' => 1]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/morning-hub/routine/blocks/reorder', [
            'blocks' => [$block2->id, $block1->id],
        ])
        ->assertNoContent();

    expect(RoutineBlock::find($block1->id)->sort_order)->toBe(1);
    expect(RoutineBlock::find($block2->id)->sort_order)->toBe(0);
});
