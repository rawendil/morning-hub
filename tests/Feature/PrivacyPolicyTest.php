<?php

test('privacy policy page is publicly accessible', function () {
    $this->get('/privacy-policy')->assertOk();
});

test('privacy policy contains contact email from config', function () {
    $this->get('/privacy-policy')
        ->assertOk()
        ->assertSee(config('app.contact_email'));
});

test('privacy policy describes cookieless Umami analytics instead of Google Analytics', function () {
    $this->get('/privacy-policy')
        ->assertOk()
        ->assertSee('Umami')
        ->assertSee('umami.disabled')
        ->assertDontSee('Google Analytics')
        ->assertDontSee('Google Tag Manager')
        ->assertDontSee('gaoptout', false);
});

test('privacy policy states that Do Not Track is honoured', function () {
    $this->get('/privacy-policy')
        ->assertOk()
        ->assertDontSee('nie reagujemy na te sygnały')
        ->assertSee('Respektujemy ten sygnał');
});

test('privacy policy describes what Umami actually derives from the ip address', function () {
    $this->get('/privacy-policy')
        ->assertOk()
        ->assertDontSee('nie pozwala rozpoznać Cię przy kolejnej wizycie')
        ->assertDontSee('służy wyłącznie do wyznaczenia kraju')
        ->assertSee('przybliżonej lokalizacji (kraj, region, miasto)')
        ->assertSee('zmienia się co miesiąc');
});

test('privacy policy names the preference cookies instead of claiming only necessary ones', function () {
    $this->get('/privacy-policy')
        ->assertOk()
        ->assertDontSee('wyłącznie niezbędnych plików cookie')
        ->assertSee('appearance')
        ->assertSee('locale')
        ->assertSee('sidebar_state');
});
