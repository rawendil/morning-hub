<?php

use App\Enums\BlockType;
use App\Models\RoutineBlock;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->block = RoutineBlock::factory()->for($this->user)->create([
        'type' => BlockType::Habits,
        'config' => ['habits' => ['Watch video', 'Exercise', 'Read']],
    ]);
});

test('guest cannot toggle habits', function () {
    $this->postJson(route('morning-hub.habits.toggle', $this->block), ['index' => 0])
        ->assertUnauthorized();
});

test('it toggles a habit on', function () {
    $this->actingAs($this->user)
        ->postJson(route('morning-hub.habits.toggle', $this->block), ['index' => 0])
        ->assertSuccessful()
        ->assertJson(['completed' => [0]]);
});

test('it toggles a habit off', function () {
    session(["habits_block_{$this->block->id}" => [
        'date' => now()->toDateString(),
        'completed' => [0, 1],
    ]]);

    $this->actingAs($this->user)
        ->postJson(route('morning-hub.habits.toggle', $this->block), ['index' => 0])
        ->assertSuccessful()
        ->assertJson(['completed' => [1]]);
});

test('it resets completed habits on a new day', function () {
    session(["habits_block_{$this->block->id}" => [
        'date' => now()->subDay()->toDateString(),
        'completed' => [0, 1, 2],
    ]]);

    $this->actingAs($this->user)
        ->postJson(route('morning-hub.habits.toggle', $this->block), ['index' => 1])
        ->assertSuccessful()
        ->assertJson(['completed' => [1]]);
});

test('it rejects invalid habit index', function () {
    $this->actingAs($this->user)
        ->postJson(route('morning-hub.habits.toggle', $this->block), ['index' => 99])
        ->assertUnprocessable();
});

test('it prevents toggling habits on another users block', function () {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->postJson(route('morning-hub.habits.toggle', $this->block), ['index' => 0])
        ->assertForbidden();
});

test('it requires index parameter', function () {
    $this->actingAs($this->user)
        ->postJson(route('morning-hub.habits.toggle', $this->block), [])
        ->assertUnprocessable();
});

test('dashboard passes habit completions from session', function () {
    session(["habits_block_{$this->block->id}" => [
        'date' => now()->toDateString(),
        'completed' => [0, 2],
    ]]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where("habits_{$this->block->id}", [0, 2])
        );
});

test('dashboard returns empty completions for stale session date', function () {
    session(["habits_block_{$this->block->id}" => [
        'date' => now()->subDay()->toDateString(),
        'completed' => [0, 1],
    ]]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where("habits_{$this->block->id}", [])
        );
});
