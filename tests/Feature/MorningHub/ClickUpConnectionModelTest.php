<?php

use App\Models\ClickUpConnection;
use App\Models\User;

test('user has many clickup connections', function () {
    $user = User::factory()->create();

    $connection = ClickUpConnection::factory()->for($user)->create();

    expect($user->clickUpConnections)->toHaveCount(1);
    expect($user->clickUpConnections->first()->id)->toBe($connection->id);
});

test('clickup connection belongs to user', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    expect($connection->user->id)->toBe($user->id);
});

test('api token is encrypted in database', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'api_token' => 'pk_test_token_12345',
    ]);

    // Raw DB value should not match the plain token
    $raw = DB::table('clickup_connections')->where('id', $connection->id)->value('api_token');
    expect($raw)->not->toBe('pk_test_token_12345');

    // But the model should decrypt it
    expect($connection->fresh()->api_token)->toBe('pk_test_token_12345');
});

test('api token is hidden from serialization', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    expect(array_key_exists('api_token', $connection->toArray()))->toBeFalse();
});

test('default_filters is cast to array', function () {
    $user = User::factory()->create();
    $filters = ['assignee' => 'me', 'due_date' => 'today'];
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_filters' => $filters,
    ]);

    expect($connection->fresh()->default_filters)->toBe($filters);
});
