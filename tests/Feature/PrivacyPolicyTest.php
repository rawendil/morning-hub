<?php

test('privacy policy page is publicly accessible', function () {
    $this->get('/privacy-policy')->assertOk();
});

test('privacy policy contains contact email from config', function () {
    $this->get('/privacy-policy')
        ->assertOk()
        ->assertSee(config('app.contact_email'));
});
