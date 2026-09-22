<?php

use App\Enums\BlockType;
use App\Models\RoutineBlock;
use App\Models\User;

function habitsBlockFor(User $user): RoutineBlock
{
    return RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Habits,
        'config' => ['habits' => [['id' => 'habit-water', 'label' => 'Woda']]],
    ]);
}

test('guest cannot read daily progress', function () {
    $this->getJson('/api/morning-hub/daily')->assertUnauthorized();
});

test('daily progress starts empty', function () {
    $user = User::factory()->create(['timezone' => 'Europe/Warsaw']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/daily')
        ->assertOk()
        ->assertJsonPath('date', $user->localDate()->toDateString())
        ->assertJsonPath('habits', [])
        ->assertJsonPath('blocks', []);
});

test('user can complete a habit and read it back', function () {
    $user = User::factory()->create();
    $block = habitsBlockFor($user);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/morning-hub/daily/blocks/{$block->id}/habits", [
            'habit_id' => 'habit-water',
            'completed' => true,
        ])
        ->assertOk()
        ->assertJsonPath('habits.0.habit_id', 'habit-water');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/daily')
        ->assertJsonPath('habits.0.habit_id', 'habit-water');
});

test('user can uncheck a habit', function () {
    $user = User::factory()->create();
    $block = habitsBlockFor($user);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/morning-hub/daily/blocks/{$block->id}/habits", [
            'habit_id' => 'habit-water',
            'completed' => true,
        ])->assertOk();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/morning-hub/daily/blocks/{$block->id}/habits", [
            'habit_id' => 'habit-water',
            'completed' => false,
        ])
        ->assertOk()
        ->assertJsonPath('habits', []);
});

test('a habit that does not belong to the block is rejected', function () {
    $user = User::factory()->create();
    $block = habitsBlockFor($user);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/morning-hub/daily/blocks/{$block->id}/habits", [
            'habit_id' => 'habit-unknown',
            'completed' => true,
        ])
        ->assertJsonValidationErrors('habit_id');
});

test('user cannot record a habit on another users block', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $block = habitsBlockFor($owner);

    $this->actingAs($other, 'sanctum')
        ->postJson("/api/morning-hub/daily/blocks/{$block->id}/habits", [
            'habit_id' => 'habit-water',
            'completed' => true,
        ])
        ->assertForbidden();
});

test('user can record a completed block with the measured time', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/morning-hub/daily/blocks/{$block->id}", [
            'status' => 'completed',
            'elapsed_seconds' => 742,
        ])
        ->assertOk()
        ->assertJsonPath('blocks.0.status', 'completed')
        ->assertJsonPath('blocks.0.elapsed_seconds', 742);
});

test('an unknown block status is rejected', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/morning-hub/daily/blocks/{$block->id}", ['status' => 'procrastinated'])
        ->assertJsonValidationErrors('status');
});

test('user cannot record another users block', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $block = RoutineBlock::factory()->for($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->putJson("/api/morning-hub/daily/blocks/{$block->id}", ['status' => 'completed'])
        ->assertForbidden();
});

test('user can clear a block completion', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/morning-hub/daily/blocks/{$block->id}", ['status' => 'completed'])
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/morning-hub/daily/blocks/{$block->id}")
        ->assertOk()
        ->assertJsonPath('blocks', []);
});
