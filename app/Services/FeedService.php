<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class FeedService
{
    /**
     * Fetch and parse articles from multiple RSS/Atom feeds.
     *
     * @param  array<int, array{name: string, url: string}>  $sources
     * @return array<int, array{title: string, link: string, source: string, published_at: string}>
     */
    public function fetchArticles(array $sources, int $days = 5): array
    {
        $cutoff = now()->subDays($days)->startOfDay();
        $articles = [];

        foreach ($sources as $source) {
            try {
                $response = Http::timeout(10)->get($source['url']);

                if (! $response->successful()) {
                    continue;
                }

                $items = $this->parseXml($response->body(), $source['name']);

                foreach ($items as $item) {
                    if (Carbon::parse($item['published_at'])->gte($cutoff)) {
                        $articles[] = $item;
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        usort($articles, fn (array $a, array $b) => Carbon::parse($b['published_at'])->getTimestamp() - Carbon::parse($a['published_at'])->getTimestamp());

        return $articles;
    }

    /**
     * @return array<int, array{title: string, link: string, source: string, published_at: string}>
     */
    private function parseXml(string $body, string $sourceName): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            return [];
        }

        if (isset($xml->channel->item)) {
            return $this->parseRss($xml, $sourceName);
        }

        if ($xml->getName() === 'feed') {
            return $this->parseAtom($xml, $sourceName);
        }

        return [];
    }

    /**
     * @return array<int, array{title: string, link: string, source: string, published_at: string}>
     */
    private function parseRss(\SimpleXMLElement $xml, string $sourceName): array
    {
        $items = [];

        foreach ($xml->channel->item as $item) {
            $title = (string) $item->title;
            $link = (string) $item->link;
            $pubDate = (string) $item->pubDate;

            if ($title === '' || $link === '' || $pubDate === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'link' => $link,
                'source' => $sourceName,
                'published_at' => Carbon::parse($pubDate)->toIso8601String(),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{title: string, link: string, source: string, published_at: string}>
     */
    private function parseAtom(\SimpleXMLElement $xml, string $sourceName): array
    {
        $items = [];

        foreach ($xml->entry as $entry) {
            $title = (string) $entry->title;
            $link = '';
            $date = (string) ($entry->published ?: $entry->updated);

            foreach ($entry->link as $linkEl) {
                $rel = (string) $linkEl['rel'];
                if ($rel === 'alternate' || $rel === '') {
                    $link = (string) $linkEl['href'];
                    break;
                }
            }

            if ($link === '' && isset($entry->link[0])) {
                $link = (string) $entry->link[0]['href'];
            }

            if ($title === '' || $link === '' || $date === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'link' => $link,
                'source' => $sourceName,
                'published_at' => Carbon::parse($date)->toIso8601String(),
            ];
        }

        return $items;
    }
}
