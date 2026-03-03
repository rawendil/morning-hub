<?php

use App\Models\ClickUpConnection;
use App\Models\RoutineBlock;
use App\Models\User;

test('user can view own clickup connection', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    expect($user->can('view', $connection))->toBeTrue();
});

test('user cannot view another users clickup connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    expect($user->can('view', $connection))->toBeFalse();
});

test('user can update own clickup connection', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    expect($user->can('update', $connection))->toBeTrue();
});

test('user cannot update another users clickup connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    expect($user->can('update', $connection))->toBeFalse();
});

test('user can delete own clickup connection', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    expect($user->can('delete', $connection))->toBeTrue();
});

test('user cannot delete another users clickup connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    expect($user->can('delete', $connection))->toBeFalse();
});

test('user can view own routine block', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    expect($user->can('view', $block))->toBeTrue();
});

test('user cannot view another users routine block', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $block = RoutineBlock::factory()->for($otherUser)->create();

    expect($user->can('view', $block))->toBeFalse();
});

test('user can update own routine block', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    expect($user->can('update', $block))->toBeTrue();
});

test('user cannot update another users routine block', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $block = RoutineBlock::factory()->for($otherUser)->create();

    expect($user->can('update', $block))->toBeFalse();
});

test('user can delete own routine block', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    expect($user->can('delete', $block))->toBeTrue();
});

test('user cannot delete another users routine block', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $block = RoutineBlock::factory()->for($otherUser)->create();

    expect($user->can('delete', $block))->toBeFalse();
});
