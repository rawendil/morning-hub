<?php

use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The terms of service text with every run of whitespace collapsed to one space,
 * so a phrase matches no matter where the formatter wraps the template lines.
 */
function termsOfServiceText(TestCase $testCase): string
{
    return Str::squish($testCase->get('/terms-of-service')->assertOk()->getContent());
}

test('terms of service show the company tax and statistical numbers when configured', function () {
    config(['app.company_nip' => '0000000000', 'app.company_regon' => '000000000']);

    expect(termsOfServiceText($this))
        ->toContain('NIP: 0000000000')
        ->toContain('REGON: 000000000');
});

test('terms of service omit the tax and statistical number lines when they are not configured', function () {
    config(['app.company_nip' => '', 'app.company_regon' => '']);

    expect(termsOfServiceText($this))
        ->not->toContain('NIP:')
        ->not->toContain('REGON:');
});
