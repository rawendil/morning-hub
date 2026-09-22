<?php

use App\Enums\BlockCompletionStatus;
use App\Models\DailyBlockCompletion;
use App\Models\DailyHabitCompletion;
use App\Models\RoutineBlock;
use App\Models\User;
use App\Services\DailyProgressService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = app(DailyProgressService::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('completing a habit stores one entry for the user local date', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-22 23:30:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'Europe/Warsaw']);
    $block = RoutineBlock::factory()->for($user)->create();

    $this->service->setHabitCompletion($user, $block, 'habit-water', true);

    $entry = DailyHabitCompletion::query()->sole();

    expect($entry->habit_id)->toBe('habit-water')
        ->and($entry->routine_block_id)->toBe($block->id)
        ->and($entry->local_date)->toBe('2026-09-23');
});

test('completing the same habit twice does not duplicate the entry', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->service->setHabitCompletion($user, $block, 'habit-water', true);
    $this->service->setHabitCompletion($user, $block, 'habit-water', true);

    expect(DailyHabitCompletion::query()->count())->toBe(1);
});

test('unchecking a habit removes its entry', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->service->setHabitCompletion($user, $block, 'habit-water', true);
    $this->service->setHabitCompletion($user, $block, 'habit-water', false);

    expect(DailyHabitCompletion::query()->count())->toBe(0);
});

test('completing a block stores its status and measured time', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->service->recordBlockCompletion($user, $block, BlockCompletionStatus::Completed, 742);

    $entry = DailyBlockCompletion::query()->sole();

    expect($entry->status)->toBe(BlockCompletionStatus::Completed)
        ->and($entry->elapsed_seconds)->toBe(742)
        ->and($entry->routine_block_id)->toBe($block->id);
});

test('recording a block again overwrites the previous entry for that day', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->service->recordBlockCompletion($user, $block, BlockCompletionStatus::Skipped, 10);
    $this->service->recordBlockCompletion($user, $block, BlockCompletionStatus::Completed, 300);

    $entry = DailyBlockCompletion::query()->sole();

    expect($entry->status)->toBe(BlockCompletionStatus::Completed)
        ->and($entry->elapsed_seconds)->toBe(300);
});

test('clearing a block completion removes its entry', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->service->recordBlockCompletion($user, $block, BlockCompletionStatus::Completed, 300);
    $this->service->clearBlockCompletion($user, $block);

    expect(DailyBlockCompletion::query()->count())->toBe(0);
});

test('state returns todays habits and blocks for the user', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->service->setHabitCompletion($user, $block, 'habit-water', true);
    $this->service->recordBlockCompletion($user, $block, BlockCompletionStatus::Completed, 120);

    $state = $this->service->stateFor($user);

    expect($state['date'])->toBe($user->localDate()->toDateString())
        ->and($state['habits'])->toBe([['routine_block_id' => $block->id, 'habit_id' => 'habit-water']])
        ->and($state['blocks'])->toBe([[
            'routine_block_id' => $block->id,
            'status' => 'completed',
            'elapsed_seconds' => 120,
        ]]);
});

test('state ignores entries from previous days', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    Carbon::setTestNow(Carbon::parse('2026-09-21 08:00:00', 'UTC'));
    $this->service->setHabitCompletion($user, $block, 'habit-water', true);

    Carbon::setTestNow(Carbon::parse('2026-09-22 08:00:00', 'UTC'));

    expect($this->service->stateFor($user)['habits'])->toBe([]);
});

test('state never leaks another users entries', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $otherBlock = RoutineBlock::factory()->for($other)->create();

    $this->service->setHabitCompletion($other, $otherBlock, 'habit-water', true);

    expect($this->service->stateFor($user)['habits'])->toBe([]);
});
