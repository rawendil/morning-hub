<?php

/**
 * Unit tests do not boot the application, so the project root is resolved from this file.
 */
function projectPath(string $path): string
{
    return dirname(__DIR__, 2).'/'.$path;
}

/**
 * @return array<string, list<string>>
 */
function composerScripts(): array
{
    /** @var array{scripts?: array<string, list<string>|string>} $composer */
    $composer = json_decode((string) file_get_contents(projectPath('composer.json')), true);

    $scripts = [];

    foreach ($composer['scripts'] ?? [] as $name => $steps) {
        $scripts[$name] = (array) $steps;
    }

    return $scripts;
}

/**
 * Expands `@other-script` references so the assertions see the commands that actually run.
 *
 * @param  array<string, list<string>>  $scripts
 * @return list<string>
 */
function resolveComposerScript(array $scripts, string $name): array
{
    $resolved = [];

    foreach ($scripts[$name] ?? [] as $step) {
        $reference = ltrim($step, '@');

        if (str_starts_with($step, '@') && isset($scripts[$reference])) {
            $resolved = [...$resolved, ...resolveComposerScript($scripts, $reference)];

            continue;
        }

        $resolved[] = $step;
    }

    return $resolved;
}

test('composer ci runs the full quality gate', function (string $command) {
    $steps = resolveComposerScript(composerScripts(), 'ci');

    expect($steps)->toContain($command);
})->with([
    'pint --parallel --test',
    'phpstan analyse --error-format=table',
    'npm run types:check',
    'npm run lint:check',
    'npm run format:check',
    'npm run test',
    '@php artisan test',
]);

test('composer ci replaces the former ci:check script', function () {
    expect(composerScripts())
        ->toHaveKey('ci')
        ->not->toHaveKey('ci:check');
});

test('the quality gate lives in the deploy workflow, not a separate CI file', function () {
    expect(projectPath('.github/workflows/ci.yml'))->not->toBeFile();
});

test('the deploy workflow runs the quality gate', function () {
    $deploy = (string) file_get_contents(projectPath('.github/workflows/deploy.yml'));

    expect($deploy)->toContain('composer ci');
});

test('deployment is blocked until the quality gate passes', function () {
    $deploy = (string) file_get_contents(projectPath('.github/workflows/deploy.yml'));

    expect($deploy)->toMatch('/^\s+deploy:\s*\n(?:\s+.*\n)*?\s+needs:\s*\[?\s*quality\s*\]?\s*$/m');
});

test('deploy workflow is triggered by a push to master', function () {
    $deploy = (string) file_get_contents(projectPath('.github/workflows/deploy.yml'));

    expect($deploy)->toMatch('/^on:\s*\n\s+push:\s*\n\s+branches:\s*\[\s*master\s*\]/m');
});

test('deploy workflow keeps no reference to the removed CI workflow', function (string $leftover) {
    $deploy = (string) file_get_contents(projectPath('.github/workflows/deploy.yml'));

    expect($deploy)->not->toContain($leftover);
})->with([
    'workflow_run',
    'conclusion',
]);
