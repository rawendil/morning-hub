<?php

test('locale can be set via post request', function () {
    $this->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect()
        ->assertPlainCookie('locale', 'en');
});

test('locale can be set to pl', function () {
    $this->post(route('locale.update'), ['locale' => 'pl'])
        ->assertRedirect()
        ->assertPlainCookie('locale', 'pl');
});

test('invalid locale is rejected', function () {
    $this->post(route('locale.update'), ['locale' => 'de'])
        ->assertSessionHasErrors('locale');
});

test('missing locale is rejected', function () {
    $this->post(route('locale.update'), [])
        ->assertSessionHasErrors('locale');
});
