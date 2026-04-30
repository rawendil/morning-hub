<?php

use App\Http\Middleware\HandleLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    app()->setLocale(config('app.locale'));
});

test('locale uses app default when no cookie is set', function () {
    config(['app.locale' => 'pl']);

    $request = Request::create('/api/user', 'GET');
    $middleware = new HandleLocale;
    $middleware->handle($request, fn () => new Response);

    expect(app()->getLocale())->toBe('pl');
});

test('locale is set to en from cookie', function () {
    $request = Request::create('/api/user', 'GET', [], ['locale' => 'en']);
    $middleware = new HandleLocale;
    $middleware->handle($request, fn () => new Response);

    expect(app()->getLocale())->toBe('en');
});

test('locale is set to pl from cookie', function () {
    $request = Request::create('/api/user', 'GET', [], ['locale' => 'pl']);
    $middleware = new HandleLocale;
    $middleware->handle($request, fn () => new Response);

    expect(app()->getLocale())->toBe('pl');
});

test('invalid locale falls back to app default', function () {
    config(['app.locale' => 'pl']);

    $request = Request::create('/api/user', 'GET', [], ['locale' => 'de']);
    $middleware = new HandleLocale;
    $middleware->handle($request, fn () => new Response);

    expect(app()->getLocale())->toBe('pl');
});
