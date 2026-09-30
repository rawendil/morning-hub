<?php

beforeEach(function () {
    config([
        'services.umami.url' => 'https://umami-upstream.example.test',
        'services.umami.website_id' => 'ffffffff-0000-4000-8000-000000000001',
    ]);
});

test('renders the tracker tag pointing at the app domain', function () {
    $content = $this->get('/')->assertOk()->getContent();

    expect($content)
        ->toMatch('/<script\s+defer\s+src="'.preg_quote(url('/api/mh.js'), '/').'"/')
        ->toContain('data-website-id="ffffffff-0000-4000-8000-000000000001"')
        ->toContain('data-host-url="'.url('/').'"')
        ->toContain('data-do-not-track="true"');
});

test('strips query strings so reset tokens never reach analytics', function () {
    expect($this->get('/reset-password?token=secret&email=a@b.test')->getContent())
        ->toContain('data-exclude-search="true"');
});

test('never exposes the upstream instance address', function () {
    expect($this->get('/')->getContent())->not->toContain('umami-upstream.example.test');
});

test('renders no tracker when analytics is switched off', function () {
    config(['services.umami.website_id' => null]);

    expect($this->get('/')->getContent())
        ->not->toContain('/api/mh.js')
        ->not->toContain('data-website-id');
});

test('leaves no Google Analytics code behind', function () {
    expect($this->get('/')->getContent())
        ->not->toContain('googletagmanager.com')
        ->not->toContain('gtag(')
        ->not->toContain('dataLayer');
});
