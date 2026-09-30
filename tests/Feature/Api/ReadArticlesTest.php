<?php

use App\Models\ReadArticle;
use App\Models\User;

const ARTICLE_LINK = 'https://example.test/articles/1';

test('guest cannot mark an article read', function () {
    $this->postJson('/api/morning-hub/read-articles', [
        'link' => ARTICLE_LINK,
        'read' => true,
    ])->assertUnauthorized();
});

test('user can mark an article read and gets the read links back', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/read-articles', ['link' => ARTICLE_LINK, 'read' => true])
        ->assertOk()
        ->assertExactJson([ARTICLE_LINK]);

    expect(ReadArticle::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('marking an article read twice keeps a single record', function () {
    $user = User::factory()->create();

    foreach ([1, 2] as $attempt) {
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/morning-hub/read-articles', ['link' => ARTICLE_LINK, 'read' => true])
            ->assertOk();
    }

    expect(ReadArticle::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('user can mark an article unread', function () {
    $user = User::factory()->create();
    ReadArticle::factory()->for($user)->forLink(ARTICLE_LINK)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/read-articles', ['link' => ARTICLE_LINK, 'read' => false])
        ->assertOk()
        ->assertExactJson([]);

    expect(ReadArticle::query()->count())->toBe(0);
});

test('read articles are kept per user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    ReadArticle::factory()->for($owner)->forLink(ARTICLE_LINK)->create();

    $this->actingAs($other, 'sanctum')
        ->postJson('/api/morning-hub/read-articles', ['link' => ARTICLE_LINK, 'read' => false])
        ->assertOk();

    expect(ReadArticle::query()->where('user_id', $owner->id)->count())->toBe(1);

    $this->actingAs($other, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertJsonPath('read_articles', []);
});

test('marking an article read prunes records older than the retention window', function () {
    $user = User::factory()->create();
    ReadArticle::factory()->for($user)->forLink('https://example.test/old')->create(['read_at' => now()->subDays(31)]);
    ReadArticle::factory()->for($user)->forLink('https://example.test/recent')->create(['read_at' => now()->subDays(29)]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/read-articles', ['link' => ARTICLE_LINK, 'read' => true])
        ->assertOk()
        ->assertJsonCount(2);

    expect(ReadArticle::query()->pluck('link')->all())
        ->toEqualCanonicalizing(['https://example.test/recent', ARTICLE_LINK]);
});

test('dashboard returns read links within the retention window', function () {
    $user = User::factory()->create();
    ReadArticle::factory()->for($user)->forLink('https://example.test/old')->create(['read_at' => now()->subDays(31)]);
    ReadArticle::factory()->for($user)->forLink(ARTICLE_LINK)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('read_articles', [ARTICLE_LINK]);
});

test('marking an article read validates the payload', function (array $payload, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/read-articles', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'missing link' => [['read' => true], 'link'],
    'not a url' => [['link' => 'not-a-url', 'read' => true], 'link'],
    'too long' => [['link' => 'https://example.test/'.str_repeat('a', 2048), 'read' => true], 'link'],
    'missing read' => [['link' => ARTICLE_LINK], 'read'],
    'read not boolean' => [['link' => ARTICLE_LINK, 'read' => 'maybe'], 'read'],
]);
