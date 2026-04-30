<?php

use App\Models\User;

test('user can fetch 2fa status', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/settings/two-factor')
        ->assertOk()
        ->assertJsonStructure(['twoFactorEnabled', 'requiresConfirmation']);
});
