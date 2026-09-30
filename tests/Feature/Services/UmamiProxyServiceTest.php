<?php

use App\Services\UmamiProxyService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

const UMAMI_UPSTREAM = 'https://umami.example.test';
const UMAMI_WEBSITE_ID = 'ffffffff-0000-4000-8000-000000000001';

beforeEach(function () {
    config([
        'services.umami.url' => UMAMI_UPSTREAM,
        'services.umami.website_id' => UMAMI_WEBSITE_ID,
    ]);
});

test('is enabled only when both the upstream and the website id are configured', function (?string $url, ?string $websiteId, bool $expected) {
    config(['services.umami.url' => $url, 'services.umami.website_id' => $websiteId]);

    expect(app(UmamiProxyService::class)->isEnabled())->toBe($expected);
})->with([
    'both set' => [UMAMI_UPSTREAM, UMAMI_WEBSITE_ID, true],
    'url missing' => [null, UMAMI_WEBSITE_ID, false],
    'website id missing' => [UMAMI_UPSTREAM, null, false],
    'blank values' => ['  ', '  ', false],
]);

test('exposes the trimmed website id', function () {
    config(['services.umami.website_id' => '  '.UMAMI_WEBSITE_ID.'  ']);

    expect(app(UmamiProxyService::class)->websiteId())->toBe(UMAMI_WEBSITE_ID);
});

test('fetches the tracker script once and serves it from cache afterwards', function () {
    Http::fake([UMAMI_UPSTREAM.'/script.js' => Http::response('window.umami=1;')]);

    $service = app(UmamiProxyService::class);

    expect($service->trackerScript())->toBe('window.umami=1;')
        ->and($service->trackerScript())->toBe('window.umami=1;');

    Http::assertSentCount(1);
});

test('does not double the slash when the upstream ends with one', function () {
    config(['services.umami.url' => UMAMI_UPSTREAM.'/']);
    Http::fake([UMAMI_UPSTREAM.'/script.js' => Http::response('window.umami=1;')]);

    app(UmamiProxyService::class)->trackerScript();

    Http::assertSent(fn (Request $request) => $request->url() === UMAMI_UPSTREAM.'/script.js');
});

test('returns null and caches nothing when the upstream fails', function () {
    Http::fake([UMAMI_UPSTREAM.'/script.js' => Http::response('boom', 500)]);

    expect(app(UmamiProxyService::class)->trackerScript())->toBeNull()
        ->and(Cache::has('umami:tracker-script'))->toBeFalse();
});

test('retries the upstream on the next call after a failure', function () {
    Http::fakeSequence(UMAMI_UPSTREAM.'/script.js')
        ->push('boom', 500)
        ->push('window.umami=1;');

    $service = app(UmamiProxyService::class);

    expect($service->trackerScript())->toBeNull()
        ->and($service->trackerScript())->toBe('window.umami=1;');
});

test('returns null when the upstream is unreachable', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect(app(UmamiProxyService::class)->trackerScript())->toBeNull();
});

test('forwards the event payload untouched with the reader headers', function () {
    Http::fake([UMAMI_UPSTREAM.'/api/send' => Http::response(['ok' => true])]);

    app(UmamiProxyService::class)->forwardEvent(
        '{"type":"event","payload":{"url":"/dashboard"}}',
        '203.0.113.7',
        'Mozilla/5.0 Test',
        'pl-PL,pl;q=0.9',
    );

    Http::assertSent(fn (Request $request) => $request->url() === UMAMI_UPSTREAM.'/api/send'
        && $request->method() === 'POST'
        && $request->body() === '{"type":"event","payload":{"url":"/dashboard"}}'
        && $request->header('X-Client-IP') === ['203.0.113.7']
        && $request->header('User-Agent') === ['Mozilla/5.0 Test']
        && $request->header('Accept-Language') === ['pl-PL,pl;q=0.9']
        && $request->header('Content-Type') === ['application/json']);
});

test('swallows upstream exceptions when forwarding an event', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    app(UmamiProxyService::class)->forwardEvent('{}', '203.0.113.7', 'UA', 'pl');
})->throwsNoExceptions();
