<?php

namespace App\Services;

class TranslationService
{
    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    /**
     * @return array<string, string>
     */
    public function getTranslationsForLocale(string $locale): array
    {
        if (isset(self::$cache[$locale])) {
            return self::$cache[$locale];
        }

        $path = lang_path("{$locale}.json");

        if (! file_exists($path)) {
            return self::$cache[$locale] = [];
        }

        /** @var array<string, string> $translations */
        $translations = json_decode(file_get_contents($path), true) ?? [];

        return self::$cache[$locale] = $translations;
    }
}
