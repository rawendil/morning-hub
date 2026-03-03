<?php

use App\Models\ClickUpConnection;
use App\Models\RoutineBlock;
use App\Models\User;

test('guest cannot access routine page', function () {
    $this->get(route('morning-hub.routine.index'))->assertRedirect(route('login'));
});

test('user can view routine page with ordered blocks', function () {
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->create(['sort_order' => 2, 'name' => 'Second']);
    RoutineBlock::factory()->for($user)->create(['sort_order' => 1, 'name' => 'First']);

    $this->actingAs($user)
        ->get(route('morning-hub.routine.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('morning-hub/Routine')
            ->has('blocks', 2)
            ->where('blocks.0.name', 'First')
            ->where('blocks.1.name', 'Second')
        );
});

test('user can create a block with auto sort_order', function () {
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->create(['sort_order' => 0]);

    $this->actingAs($user)
        ->post(route('morning-hub.routine.store'), [
            'type' => 'braindump',
            'name' => 'Morning Dump',
            'timer_minutes' => 10,
        ])
        ->assertRedirect(route('morning-hub.routine.index'));

    $newBlock = $user->routineBlocks()->where('name', 'Morning Dump')->first();
    expect($newBlock)->not->toBeNull();
    expect($newBlock->sort_order)->toBe(1);
    expect($newBlock->timer_minutes)->toBe(10);
});

test('user can create clickup block with valid connection', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('morning-hub.routine.store'), [
            'type' => 'clickup',
            'name' => 'Work Tasks',
            'clickup_connection_id' => $connection->id,
        ])
        ->assertRedirect(route('morning-hub.routine.index'));

    expect($user->routineBlocks()->first()->clickup_connection_id)->toBe($connection->id);
});

test('user cannot assign another users connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->post(route('morning-hub.routine.store'), [
            'type' => 'clickup',
            'name' => 'Stolen Tasks',
            'clickup_connection_id' => $connection->id,
        ])
        ->assertSessionHasErrors('clickup_connection_id');
});

test('user can update own block', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('morning-hub.routine.update', $block), [
            'name' => 'Updated Block',
            'timer_minutes' => 15,
        ])
        ->assertRedirect(route('morning-hub.routine.index'));

    expect($block->fresh()->name)->toBe('Updated Block');
    expect($block->fresh()->timer_minutes)->toBe(15);
});

test('user cannot update another users block', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $block = RoutineBlock::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->put(route('morning-hub.routine.update', $block), [
            'name' => 'Hacked Block',
        ])
        ->assertForbidden();
});

test('user can delete own block', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('morning-hub.routine.destroy', $block))
        ->assertRedirect(route('morning-hub.routine.index'));

    expect(RoutineBlock::find($block->id))->toBeNull();
});

test('user can reorder blocks', function () {
    $user = User::factory()->create();
    $block1 = RoutineBlock::factory()->for($user)->create(['sort_order' => 0, 'name' => 'A']);
    $block2 = RoutineBlock::factory()->for($user)->create(['sort_order' => 1, 'name' => 'B']);
    $block3 = RoutineBlock::factory()->for($user)->create(['sort_order' => 2, 'name' => 'C']);

    $this->actingAs($user)
        ->patch(route('morning-hub.routine.reorder'), [
            'blocks' => [$block3->id, $block1->id, $block2->id],
        ])
        ->assertRedirect(route('morning-hub.routine.index'));

    expect($block3->fresh()->sort_order)->toBe(0);
    expect($block1->fresh()->sort_order)->toBe(1);
    expect($block2->fresh()->sort_order)->toBe(2);
});

test('reorder validates all blocks belong to user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownBlock = RoutineBlock::factory()->for($user)->create();
    $otherBlock = RoutineBlock::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->patch(route('morning-hub.routine.reorder'), [
            'blocks' => [$ownBlock->id, $otherBlock->id],
        ])
        ->assertSessionHasErrors('blocks');
});

test('routine page includes user connections for form', function () {
    $user = User::factory()->create();
    ClickUpConnection::factory()->for($user)->create(['name' => 'Work']);

    $this->actingAs($user)
        ->get(route('morning-hub.routine.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('connections', 1)
            ->where('connections.0.name', 'Work')
        );
});
