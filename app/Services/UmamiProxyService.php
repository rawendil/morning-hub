<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Transport between the browser and the self-hosted Umami instance. The instance
 * address stays server-side: the tracker script and its events travel through the
 * Morning Hub domain, so neither ad blockers nor the page source see the upstream.
 */
class UmamiProxyService
{
    /**
     * Upper bound for a forwarded tracker event. Real Umami payloads stay under 2 KB;
     * the limit keeps the public beacon from relaying bulk data to the upstream.
     */
    public const MAX_EVENT_BYTES = 16 * 1024;

    private const SCRIPT_CACHE_KEY = 'umami:tracker-script';

    private const SCRIPT_CACHE_HOURS = 24;

    public function isEnabled(): bool
    {
        return $this->upstream() !== null && $this->websiteId() !== null;
    }

    public function websiteId(): ?string
    {
        $websiteId = trim((string) config('services.umami.website_id'));

        return $websiteId !== '' ? $websiteId : null;
    }

    /**
     * The tracker script, cached for a day. A failed fetch returns null and is not
     * cached, so the next request retries the upstream.
     */
    public function trackerScript(): ?string
    {
        $cached = Cache::get(self::SCRIPT_CACHE_KEY);

        if (is_string($cached)) {
            return $cached;
        }

        $script = $this->fetchTrackerScript();

        if ($script !== null) {
            Cache::put(self::SCRIPT_CACHE_KEY, $script, now()->addHours(self::SCRIPT_CACHE_HOURS));
        }

        return $script;
    }

    /**
     * Passes a tracker event to the upstream untouched. Without the reader's IP,
     * user agent and language the upstream would profile this server instead.
     * Failures are swallowed: a lost page view is cheaper than a broken beacon.
     */
    public function forwardEvent(string $payload, string $clientIp, string $userAgent, string $acceptLanguage): void
    {
        $upstream = $this->upstream();

        if ($upstream === null) {
            return;
        }

        try {
            Http::withHeaders([
                'X-Client-IP' => $clientIp,
                'User-Agent' => $userAgent,
                'Accept-Language' => $acceptLanguage,
            ])
                ->connectTimeout(2)
                ->timeout(2)
                ->withBody($payload, 'application/json')
                ->post($upstream.'/api/send');
        } catch (Throwable) {
            return;
        }
    }

    private function fetchTrackerScript(): ?string
    {
        $upstream = $this->upstream();

        if ($upstream === null) {
            return null;
        }

        try {
            $response = Http::connectTimeout(3)->timeout(5)->get($upstream.'/script.js');
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    private function upstream(): ?string
    {
        $url = rtrim(trim((string) config('services.umami.url')), '/');

        return $url !== '' ? $url : null;
    }
}
