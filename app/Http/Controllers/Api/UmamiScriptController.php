<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UmamiProxyService;
use Illuminate\Http\Response;

class UmamiScriptController extends Controller
{
    public function __construct(private UmamiProxyService $umami) {}

    public function __invoke(): Response
    {
        abort_unless($this->umami->isEnabled(), 404);

        $script = $this->umami->trackerScript();

        return response($script ?? '', 200, [
            'Content-Type' => 'text/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.($script === null ? 60 : 86400),
        ]);
    }
}
