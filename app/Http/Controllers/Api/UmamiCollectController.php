<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UmamiProxyService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UmamiCollectController extends Controller
{
    public function __construct(private UmamiProxyService $umami) {}

    public function __invoke(Request $request): Response
    {
        abort_unless($this->umami->isEnabled(), 404);

        $this->umami->forwardEvent(
            $request->getContent(),
            (string) $request->ip(),
            (string) $request->userAgent(),
            (string) $request->header('Accept-Language'),
        );

        return response()->noContent();
    }
}
