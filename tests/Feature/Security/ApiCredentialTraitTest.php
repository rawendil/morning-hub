<?php

use App\Contracts\ApiCredentialProvider;
use App\Models\ClickUpConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('model implements ApiCredentialProvider interface', function () {
    $connection = ClickUpConnection::factory()->for(User::factory())->create();

    expect($connection)->toBeInstanceOf(ApiCredentialProvider::class);
});

test('maskedToken preserves prefix and shows last N chars', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'api_token' => 'pk_abc123def456xyz789',
    ]);

    $masked = $connection->maskedToken();

    expect($masked)->toStartWith('pk_');
    expect($masked)->toEndWith('z789');
    expect($masked)->toContain('****...');
    expect($masked)->not->toContain('abc123def456');
});

test('maskedToken handles short tokens gracefully', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'api_token' => 'pk_ab',
    ]);

    $masked = $connection->maskedToken();

    expect($masked)->toBe('********');
});

test('getDecryptedToken returns plain text token', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'api_token' => 'pk_test_decryption_12345',
    ]);

    expect($connection->getDecryptedToken())->toBe('pk_test_decryption_12345');
});

test('getDecryptedToken throws on empty token', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'api_token' => '',
    ]);

    $connection->getDecryptedToken();
})->throws(RuntimeException::class);

test('validateTokenFormat accepts valid clickup tokens', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'api_token' => 'pk_valid_token_1234567890',
    ]);

    expect($connection->validateTokenFormat())->toBeTrue();
});

test('validateTokenFormat rejects tokens without pk_ prefix', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'api_token' => 'invalid_token_no_prefix',
    ]);

    expect($connection->validateTokenFormat())->toBeFalse();
});

test('getProviderName returns correct provider string', function () {
    $connection = ClickUpConnection::factory()->for(User::factory())->create();

    expect($connection->getProviderName())->toBe('clickup');
});

test('observer logs creation with provider name', function () {
    $logFile = storage_path('logs/security-'.now()->format('Y-m-d').'.log');
    if (file_exists($logFile)) {
        unlink($logFile);
    }

    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['teams' => []], 200)]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/clickup/connections', [
            'name' => 'Provider Test',
            'api_token' => 'pk_provider_test_token',
        ]);

    expect(file_exists($logFile))->toBeTrue();
    $logContent = file_get_contents($logFile);
    expect($logContent)->toContain('clickup connection created');
    expect($logContent)->toContain('Provider Test');
});

test('observer logs masked token on token change', function () {
    $logFile = storage_path('logs/security-'.now()->format('Y-m-d').'.log');
    if (file_exists($logFile)) {
        unlink($logFile);
    }

    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['teams' => []], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'api_token' => 'pk_old_token_abcdefgh',
    ]);

    // Clear log from creation
    if (file_exists($logFile)) {
        unlink($logFile);
    }

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/morning-hub/clickup/connections/{$connection->id}", [
            'name' => $connection->name,
            'api_token' => 'pk_new_token_xyz12345',
        ]);

    expect(file_exists($logFile))->toBeTrue();
    $logContent = file_get_contents($logFile);
    expect($logContent)->toContain('clickup connection updated');
    expect($logContent)->toContain('pk_****...');
    expect($logContent)->not->toContain('pk_new_token_xyz12345');
});
