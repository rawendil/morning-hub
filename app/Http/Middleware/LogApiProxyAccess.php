<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiProxyAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        Log::channel('security')->info('API proxy access', [
            'user_id' => $request->user()?->id,
            'connection_id' => $request->route('connection')?->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
        ]);

        return $response;
    }
}
