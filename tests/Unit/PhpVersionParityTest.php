<?php

/**
 * The PHP version the lock file is solved for, as `major.minor`.
 */
function platformPhpVersion(): string
{
    /** @var array{config: array{platform: array{php: string}}} $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true);

    return implode('.', array_slice(explode('.', $composer['config']['platform']['php']), 0, 2));
}

function workflowSource(string $workflow): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/'.$workflow);
}

test('composer requires the PHP version the lock file is solved for', function () {
    /** @var array{require: array{php: string}} $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true);

    expect(platformPhpVersion())->toBe('8.4')
        ->and($composer['require']['php'])->toBe('^'.platformPhpVersion());
});

test('the quality gate runs on the platform PHP version', function (string $workflow) {
    expect(workflowSource($workflow))
        ->toContain("php-version: '".platformPhpVersion()."'");
})->with(['staging.yml', 'deploy.yml']);

test('the server deploy uses the platform PHP binary only', function (string $workflow) {
    $binary = 'php'.str_replace('.', '', platformPhpVersion());

    preg_match_all('/\bphp(\d{2})\b/', workflowSource($workflow), $binaries);

    expect($binaries[0])->not->toBeEmpty()
        ->and(array_unique($binaries[0]))->toBe([$binary]);
})->with(['staging.yml', 'deploy.yml']);
