<?php

use App\Enums\BlockType;
use App\Models\ClickUpConnection;
use App\Models\RoutineBlock;
use App\Models\User;

test('user has many routine blocks', function () {
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->count(3)->create();

    expect($user->routineBlocks)->toHaveCount(3);
});

test('routine block belongs to user', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    expect($block->user->id)->toBe($user->id);
});

test('routine block can belong to a clickup connection', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Clickup,
        'clickup_connection_id' => $connection->id,
    ]);

    expect($block->clickUpConnection->id)->toBe($connection->id);
});

test('routine block clickup connection is optional', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Braindump,
        'clickup_connection_id' => null,
    ]);

    expect($block->clickUpConnection)->toBeNull();
});

test('type is cast to BlockType enum', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Notes,
    ]);

    expect($block->fresh()->type)->toBe(BlockType::Notes);
});

test('config is cast to array', function () {
    $user = User::factory()->create();
    $config = ['show_subtasks' => true, 'max_items' => 10];
    $block = RoutineBlock::factory()->for($user)->create([
        'config' => $config,
    ]);

    expect($block->fresh()->config)->toBe($config);
});

test('ordered scope returns blocks by sort_order', function () {
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->create(['sort_order' => 3, 'name' => 'Third']);
    RoutineBlock::factory()->for($user)->create(['sort_order' => 1, 'name' => 'First']);
    RoutineBlock::factory()->for($user)->create(['sort_order' => 2, 'name' => 'Second']);

    $blocks = $user->routineBlocks()->ordered()->get();

    expect($blocks->pluck('name')->all())->toBe(['First', 'Second', 'Third']);
});
