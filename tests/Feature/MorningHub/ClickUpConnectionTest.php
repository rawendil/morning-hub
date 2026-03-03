<?php

use App\Models\ClickUpConnection;
use App\Models\RoutineBlock;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guest cannot access clickup settings', function () {
    $this->get(route('morning-hub.clickup.index'))->assertRedirect(route('login'));
});

test('user can view clickup settings page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('morning-hub.clickup.index'))->assertOk();
});

test('user can view their connections on settings page', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create(['name' => 'My Workspace']);

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('morning-hub/ClickUp')
            ->has('connections', 1)
            ->where('connections.0.name', 'My Workspace')
        );
});

test('user can create a connection with valid token', function () {
    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['teams' => []], 200)]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('morning-hub.clickup.store'), [
            'name' => 'Test Connection',
            'api_token' => 'pk_valid_token',
        ])
        ->assertRedirect(route('morning-hub.clickup.index'));

    expect($user->clickUpConnections()->count())->toBe(1);
    expect($user->clickUpConnections()->first()->name)->toBe('Test Connection');
});

test('user cannot create connection with invalid token', function () {
    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['err' => 'Token invalid'], 401)]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('morning-hub.clickup.store'), [
            'name' => 'Bad Connection',
            'api_token' => 'pk_bad_token',
        ])
        ->assertSessionHasErrors('api_token');

    expect($user->clickUpConnections()->count())->toBe(0);
});

test('user cannot create connection without name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('morning-hub.clickup.store'), [
            'api_token' => 'pk_some_token',
        ])
        ->assertSessionHasErrors('name');
});

test('user can update own connection', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('morning-hub.clickup.update', $connection), [
            'name' => 'Updated Name',
            'workspace_id' => 'ws_123',
        ])
        ->assertRedirect(route('morning-hub.clickup.index'));

    expect($connection->fresh()->name)->toBe('Updated Name');
    expect($connection->fresh()->workspace_id)->toBe('ws_123');
});

test('user cannot update another users connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->put(route('morning-hub.clickup.update', $connection), [
            'name' => 'Hacked',
        ])
        ->assertForbidden();
});

test('user can delete own connection', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('morning-hub.clickup.destroy', $connection))
        ->assertRedirect(route('morning-hub.clickup.index'));

    expect(ClickUpConnection::find($connection->id))->toBeNull();
});

test('user cannot delete another users connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->delete(route('morning-hub.clickup.destroy', $connection))
        ->assertForbidden();
});

test('user can test connection successfully', function () {
    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['teams' => []], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('morning-hub.clickup.test', $connection))
        ->assertOk()
        ->assertJson(['success' => true]);
});

test('user can test connection that fails', function () {
    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['err' => 'Token invalid'], 401)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('morning-hub.clickup.test', $connection))
        ->assertOk()
        ->assertJson(['success' => false]);
});

test('user can update connection with default_list_ids', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('morning-hub.clickup.update', $connection), [
            'name' => $connection->name,
            'default_list_ids' => ['l1', 'l2', 'l3'],
        ])
        ->assertRedirect(route('morning-hub.clickup.index'));

    $connection->refresh();
    expect($connection->default_list_ids)->toBe(['l1', 'l2', 'l3']);
    expect($connection->default_list_id)->toBe('l1');
});

test('updating default_list_ids to null clears default_list_id', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_ids' => ['l1', 'l2'],
        'default_list_id' => 'l1',
    ]);

    $this->actingAs($user)
        ->put(route('morning-hub.clickup.update', $connection), [
            'name' => $connection->name,
            'default_list_ids' => null,
        ])
        ->assertRedirect(route('morning-hub.clickup.index'));

    $connection->refresh();
    expect($connection->default_list_ids)->toBeNull();
    expect($connection->default_list_id)->toBeNull();
});

test('deleting connection nullifies related blocks', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => 'clickup',
        'clickup_connection_id' => $connection->id,
    ]);

    $this->actingAs($user)
        ->delete(route('morning-hub.clickup.destroy', $connection));

    expect($block->fresh()->clickup_connection_id)->toBeNull();
});
