<?php

use App\Models\User;

test('guest cannot access guide page', function () {
    $this->get(route('morning-hub.guide'))->assertRedirect(route('login'));
});

test('user can view guide page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('morning-hub.guide'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('morning-hub/Guide'));
});
