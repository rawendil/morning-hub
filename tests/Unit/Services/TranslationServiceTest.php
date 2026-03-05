<?php

use App\Services\TranslationService;

test('returns translations from json file', function () {
    $service = new TranslationService;

    file_put_contents(lang_path('test-locale.json'), json_encode([
        'Hello' => 'Cześć',
        'Save' => 'Zapisz',
    ]));

    $translations = $service->getTranslationsForLocale('test-locale');

    expect($translations)->toBe([
        'Hello' => 'Cześć',
        'Save' => 'Zapisz',
    ]);

    unlink(lang_path('test-locale.json'));
});

test('returns empty array when file does not exist', function () {
    $service = new TranslationService;

    $translations = $service->getTranslationsForLocale('nonexistent');

    expect($translations)->toBe([]);
});
