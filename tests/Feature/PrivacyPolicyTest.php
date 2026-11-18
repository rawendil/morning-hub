<?php

use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The privacy policy text with every run of whitespace collapsed to one space,
 * so a phrase matches no matter where the formatter wraps the template lines.
 */
function privacyPolicyText(TestCase $testCase): string
{
    return Str::squish($testCase->get('/privacy-policy')->assertOk()->getContent());
}

test('privacy policy page is publicly accessible', function () {
    $this->get('/privacy-policy')->assertOk();
});

test('privacy policy contains contact email from config', function () {
    expect(privacyPolicyText($this))->toContain(config('app.contact_email'));
});

test('privacy policy describes cookieless Umami analytics instead of Google Analytics', function () {
    expect(privacyPolicyText($this))
        ->toContain('Umami')
        ->toContain('umami.disabled')
        ->not->toContain('Google Analytics')
        ->not->toContain('Google Tag Manager')
        ->not->toContain('gaoptout');
});

test('privacy policy states that Do Not Track is honoured', function () {
    expect(privacyPolicyText($this))
        ->not->toContain('nie reagujemy na te sygnały')
        ->toContain('Respektujemy ten sygnał');
});

test('privacy policy describes what Umami actually derives from the ip address', function () {
    expect(privacyPolicyText($this))
        ->not->toContain('nie pozwala rozpoznać Cię przy kolejnej wizycie')
        ->not->toContain('służy wyłącznie do wyznaczenia kraju')
        ->toContain('przybliżonej lokalizacji (kraj, region, miasto)')
        ->toContain('zmienia się co miesiąc');
});

test('privacy policy names the preference cookies instead of claiming only necessary ones', function () {
    expect(privacyPolicyText($this))
        ->not->toContain('wyłącznie niezbędnych plików cookie')
        ->toContain('appearance')
        ->toContain('locale')
        ->toContain('sidebar_state');
});

test('privacy policy lists the recorded acceptance of the legal documents', function () {
    expect(privacyPolicyText($this))
        ->toContain('data i godzina akceptacji Regulaminu i Polityki prywatności wraz z potwierdzeniem pełnoletności');
});

test('privacy policy shows the company tax and statistical numbers when configured', function () {
    config(['app.company_nip' => '0000000000', 'app.company_regon' => '000000000']);

    expect(privacyPolicyText($this))
        ->toContain('NIP: 0000000000')
        ->toContain('REGON: 000000000');
});

test('privacy policy omits the tax and statistical number lines when they are not configured', function () {
    config(['app.company_nip' => '', 'app.company_regon' => '']);

    expect(privacyPolicyText($this))
        ->not->toContain('NIP:')
        ->not->toContain('REGON:');
});
