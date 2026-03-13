<?php

namespace App\Contracts;

interface ApiCredentialProvider
{
    public function getDecryptedToken(string $attribute = 'api_token'): string;

    public function maskedToken(string $attribute = 'api_token', int $visibleChars = 4): string;

    public function getProviderName(): string;

    public function validateTokenFormat(string $attribute = 'api_token'): bool;

    /** @return string[] */
    public function getCredentialAttributes(): array;
}
