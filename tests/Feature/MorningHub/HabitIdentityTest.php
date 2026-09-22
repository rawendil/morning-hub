<?php

use App\Enums\BlockType;
use App\Models\RoutineBlock;
use App\Models\User;

test('creating a habits block assigns a stable id to every habit', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/routine/blocks', [
            'type' => 'habits',
            'name' => 'Codzienne nawyki',
            'config' => ['habits' => [['label' => 'Woda'], ['label' => 'Medytacja']]],
        ])
        ->assertCreated();

    $habits = RoutineBlock::query()->where('user_id', $user->id)->sole()->config['habits'];

    expect($habits)->toHaveCount(2)
        ->and($habits[0]['label'])->toBe('Woda')
        ->and($habits[1]['label'])->toBe('Medytacja')
        ->and($habits[0]['id'])->not->toBeEmpty()
        ->and($habits[1]['id'])->not->toBe($habits[0]['id']);
});

test('plain string habits are accepted and given ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/routine/blocks', [
            'type' => 'habits',
            'name' => 'Codzienne nawyki',
            'config' => ['habits' => ['Woda', 'Medytacja']],
        ])
        ->assertCreated();

    $habits = RoutineBlock::query()->where('user_id', $user->id)->sole()->config['habits'];

    expect($habits[0]['label'])->toBe('Woda')
        ->and($habits[0]['id'])->not->toBeEmpty();
});

test('updating a habits block keeps the ids of habits that already have one', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Habits,
        'config' => ['habits' => [['id' => 'habit-keep', 'label' => 'Woda']]],
    ]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/morning-hub/routine/blocks/{$block->id}", [
            'type' => 'habits',
            'name' => $block->name,
            'config' => ['habits' => [
                ['id' => 'habit-keep', 'label' => 'Woda mineralna'],
                ['label' => 'Rozciąganie'],
            ]],
        ])
        ->assertOk();

    $habits = $block->fresh()->config['habits'];

    expect($habits[0]['id'])->toBe('habit-keep')
        ->and($habits[0]['label'])->toBe('Woda mineralna')
        ->and($habits[1]['id'])->not->toBeEmpty()
        ->and($habits[1]['id'])->not->toBe('habit-keep');
});

test('reordering habits does not change their ids', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Habits,
        'config' => ['habits' => [
            ['id' => 'habit-a', 'label' => 'Woda'],
            ['id' => 'habit-b', 'label' => 'Medytacja'],
        ]],
    ]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/morning-hub/routine/blocks/{$block->id}", [
            'type' => 'habits',
            'name' => $block->name,
            'config' => ['habits' => [
                ['id' => 'habit-b', 'label' => 'Medytacja'],
                ['id' => 'habit-a', 'label' => 'Woda'],
            ]],
        ])
        ->assertOk();

    expect(array_column($block->fresh()->config['habits'], 'id'))->toBe(['habit-b', 'habit-a']);
});
