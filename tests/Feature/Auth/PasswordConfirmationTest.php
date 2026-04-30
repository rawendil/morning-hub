<?php

use App\Models\User;

test('confirm password screen can be rendered', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();
    $response->assertViewIs('spa');
});

test('password confirmation requires authentication', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});
