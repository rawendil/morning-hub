<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const PROXY_UPSTREAM = 'https://umami.example.test';

beforeEach(function () {
    config([
        'services.umami.url' => PROXY_UPSTREAM,
        'services.umami.website_id' => 'ffffffff-0000-4000-8000-000000000001',
    ]);
});

test('serves the tracker script as cacheable javascript', function () {
    Http::fake([PROXY_UPSTREAM.'/script.js' => Http::response('window.umami=1;')]);

    $response = $this->get('/api/mh.js');

    $response->assertOk();
    expect($response->getContent())->toBe('window.umami=1;')
        ->and($response->headers->get('Content-Type'))->toBe('text/javascript; charset=UTF-8')
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=86400')
        ->and($response->headers->get('Cache-Control'))->toContain('public');
});

test('serves an empty script with a short cache when the upstream is down', function () {
    Http::fake([PROXY_UPSTREAM.'/script.js' => Http::response('boom', 500)]);

    $response = $this->get('/api/mh.js');

    $response->assertOk();
    expect($response->getContent())->toBe('')
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=60');
});

test('is not swallowed by the SPA catch-all route', function () {
    Http::fake([PROXY_UPSTREAM.'/script.js' => Http::response('window.umami=1;')]);

    expect($this->get('/api/mh.js')->getContent())->not->toContain('<!DOCTYPE html>');
});

test('forwards the beacon and always answers 204', function () {
    Http::fake([PROXY_UPSTREAM.'/api/send' => Http::response('boom', 500)]);

    $this->call('POST', '/api/send', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"type":"event"}')
        ->assertNoContent();

    Http::assertSent(fn (Request $request) => $request->url() === PROXY_UPSTREAM.'/api/send'
        && $request->body() === '{"type":"event"}');
});

test('forwards the reader ip from behind the hosting proxy', function () {
    Http::fake([PROXY_UPSTREAM.'/api/send' => Http::response()]);

    $this->postJson('/api/send', [], [
        'X-Forwarded-For' => '203.0.113.7',
        'User-Agent' => 'Mozilla/5.0 Test',
        'Accept-Language' => 'pl-PL',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request) => $request->header('X-Client-IP') === ['203.0.113.7']
        && $request->header('User-Agent') === ['Mozilla/5.0 Test']
        && $request->header('Accept-Language') === ['pl-PL']);
});

test('sets no cookies on either proxy route', function () {
    Http::fake([
        PROXY_UPSTREAM.'/script.js' => Http::response('window.umami=1;'),
        PROXY_UPSTREAM.'/api/send' => Http::response(),
    ]);

    expect($this->get('/api/mh.js')->headers->getCookies())->toBeEmpty()
        ->and($this->postJson('/api/send', [])->headers->getCookies())->toBeEmpty();
});

test('answers 404 on both routes when analytics is switched off', function () {
    config(['services.umami.url' => null]);
    Http::fake();

    $this->get('/api/mh.js')->assertNotFound();
    $this->postJson('/api/send', [])->assertNotFound();

    Http::assertNothingSent();
});

test('throttles the beacon', function () {
    Http::fake([PROXY_UPSTREAM.'/api/send' => Http::response()]);

    foreach (range(1, 120) as $attempt) {
        $this->postJson('/api/send', [])->assertNoContent();
    }

    $this->postJson('/api/send', [])->assertTooManyRequests();
});
