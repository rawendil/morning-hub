<?php

use App\Services\FeedService;
use Illuminate\Support\Facades\Http;

function rssXml(array $items): string
{
    $xmlItems = '';
    foreach ($items as $item) {
        $xmlItems .= "<item><title>{$item['title']}</title><link>{$item['link']}</link><pubDate>{$item['date']}</pubDate></item>";
    }

    return "<?xml version=\"1.0\"?><rss version=\"2.0\"><channel><title>Test</title>{$xmlItems}</channel></rss>";
}

function atomXml(array $entries): string
{
    $xmlEntries = '';
    foreach ($entries as $entry) {
        $xmlEntries .= "<entry><title>{$entry['title']}</title><link href=\"{$entry['link']}\" rel=\"alternate\"/><published>{$entry['date']}</published></entry>";
    }

    return "<?xml version=\"1.0\"?><feed xmlns=\"http://www.w3.org/2005/Atom\"><title>Test</title>{$xmlEntries}</feed>";
}

it('parses RSS 2.0 feed', function () {
    Http::fake([
        'https://example.com/rss' => Http::response(rssXml([
            ['title' => 'First Post', 'link' => 'https://example.com/1', 'date' => now()->subDay()->toRfc2822String()],
        ])),
    ]);

    $service = new FeedService;
    $articles = $service->fetchArticles([
        ['name' => 'Example', 'url' => 'https://example.com/rss'],
    ], 5);

    expect($articles)->toHaveCount(1)
        ->and($articles[0]['title'])->toBe('First Post')
        ->and($articles[0]['link'])->toBe('https://example.com/1')
        ->and($articles[0]['source'])->toBe('Example');
});

it('parses Atom feed', function () {
    Http::fake([
        'https://example.com/atom' => Http::response(atomXml([
            ['title' => 'Atom Post', 'link' => 'https://example.com/atom/1', 'date' => now()->subHours(3)->toIso8601String()],
        ])),
    ]);

    $service = new FeedService;
    $articles = $service->fetchArticles([
        ['name' => 'Atom Blog', 'url' => 'https://example.com/atom'],
    ], 5);

    expect($articles)->toHaveCount(1)
        ->and($articles[0]['title'])->toBe('Atom Post')
        ->and($articles[0]['source'])->toBe('Atom Blog');
});

it('filters articles by date', function () {
    Http::fake([
        'https://example.com/rss' => Http::response(rssXml([
            ['title' => 'Recent', 'link' => 'https://example.com/1', 'date' => now()->subDay()->toRfc2822String()],
            ['title' => 'Old', 'link' => 'https://example.com/2', 'date' => now()->subDays(10)->toRfc2822String()],
        ])),
    ]);

    $service = new FeedService;
    $articles = $service->fetchArticles([
        ['name' => 'Test', 'url' => 'https://example.com/rss'],
    ], 5);

    expect($articles)->toHaveCount(1)
        ->and($articles[0]['title'])->toBe('Recent');
});

it('handles failed source gracefully', function () {
    Http::fake([
        'https://broken.com/rss' => Http::response('', 500),
        'https://example.com/rss' => Http::response(rssXml([
            ['title' => 'Works', 'link' => 'https://example.com/1', 'date' => now()->subHour()->toRfc2822String()],
        ])),
    ]);

    $service = new FeedService;
    $articles = $service->fetchArticles([
        ['name' => 'Broken', 'url' => 'https://broken.com/rss'],
        ['name' => 'Working', 'url' => 'https://example.com/rss'],
    ], 5);

    expect($articles)->toHaveCount(1)
        ->and($articles[0]['source'])->toBe('Working');
});

it('returns empty for empty sources', function () {
    $service = new FeedService;
    $articles = $service->fetchArticles([], 5);

    expect($articles)->toBeEmpty();
});

it('sorts articles by date descending', function () {
    Http::fake([
        'https://a.com/rss' => Http::response(rssXml([
            ['title' => 'Older', 'link' => 'https://a.com/1', 'date' => now()->subDays(2)->toRfc2822String()],
        ])),
        'https://b.com/rss' => Http::response(rssXml([
            ['title' => 'Newer', 'link' => 'https://b.com/1', 'date' => now()->subHour()->toRfc2822String()],
        ])),
    ]);

    $service = new FeedService;
    $articles = $service->fetchArticles([
        ['name' => 'A', 'url' => 'https://a.com/rss'],
        ['name' => 'B', 'url' => 'https://b.com/rss'],
    ], 5);

    expect($articles)->toHaveCount(2)
        ->and($articles[0]['title'])->toBe('Newer')
        ->and($articles[1]['title'])->toBe('Older');
});
