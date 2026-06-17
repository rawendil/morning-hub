<?php

test('landing page is publicly accessible', function () {
    $this->get('/')->assertOk();
});

test('landing page exposes Open Graph meta tags for link previews', function () {
    $response = $this->get('/')->assertOk();

    $response->assertSee('property="og:type"', false);
    $response->assertSee('content="website"', false);
    $response->assertSee('property="og:title"', false);
    $response->assertSee('property="og:description"', false);
    $response->assertSee('property="og:url"', false);
    $response->assertSee('property="og:image"', false);
    $response->assertSee('images/og-image.png', false);
    $response->assertSee('property="og:image:width"', false);
    $response->assertSee('property="og:image:height"', false);
});

test('landing page exposes Twitter Card meta tags', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('name="twitter:card"', false)
        ->assertSee('content="summary_large_image"', false)
        ->assertSee('name="twitter:image"', false);
});

test('landing page meta carries the product copy', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Twoja poranna rutyna', false)
        ->assertSee('Jeden panel do zadań', false);
});
