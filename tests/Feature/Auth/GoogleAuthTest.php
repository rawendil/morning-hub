<?php

use App\Models\User;

test('guests cannot access google link routes', function () {
    $this->get(route('google.link'))
        ->assertRedirect(route('login'));

    $this->delete(route('google.unlink'))
        ->assertRedirect(route('login'));
});
