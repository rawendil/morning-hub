<?php

use App\Models\User;

test('user can fetch profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/settings/profile')
        ->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
});

test('user can update profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/settings/profile', ['name' => 'New Name', 'email' => $user->email])
        ->assertOk();

    expect($user->fresh()->name)->toBe('New Name');
});

test('user can delete account', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/settings/profile', ['password' => 'password'])
        ->assertNoContent();

    expect(User::find($user->id))->toBeNull();
});
