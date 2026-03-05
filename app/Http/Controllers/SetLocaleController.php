<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class SetLocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string', 'in:pl,en'],
        ]);

        $cookie = Cookie::create('locale')
            ->withValue($request->input('locale'))
            ->withExpires(now()->addYear())
            ->withPath('/')
            ->withSameSite('lax');

        return back()->withCookie($cookie);
    }
}
