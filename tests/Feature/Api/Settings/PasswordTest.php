<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can update password', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/settings/password', [
            'current_password' => 'old-password',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertOk();
});

test('update password fails with wrong current password', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/settings/password', [
            'current_password' => 'wrong',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertUnprocessable();
});
