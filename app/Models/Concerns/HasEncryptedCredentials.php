<?php

namespace App\Models\Concerns;

trait HasEncryptedCredentials
{
    public function getDecryptedToken(string $attribute = 'api_token'): string
    {
        $value = $this->getAttribute($attribute);

        if ($value === null || $value === '') {
            throw new \RuntimeException("Token attribute [{$attribute}] is empty.");
        }

        return $value;
    }

    public function maskedToken(string $attribute = 'api_token', int $visibleChars = 4): string
    {
        $value = $this->getAttribute($attribute);

        if (! $value || strlen($value) <= $visibleChars + 4) {
            return str_repeat('*', 8);
        }

        $prefix = substr($value, 0, 3);
        $suffix = substr($value, -$visibleChars);

        return $prefix.'****...'.$suffix;
    }

    public function validateTokenFormat(string $attribute = 'api_token'): bool
    {
        $pattern = static::tokenFormatPattern();

        if ($pattern === null) {
            return true;
        }

        $value = $this->getAttribute($attribute);

        return $value !== null && (bool) preg_match($pattern, $value);
    }

    public function getProviderName(): string
    {
        return str(class_basename(static::class))
            ->before('Connection')
            ->snake()
            ->replace('_', '')
            ->toString();
    }

    /**
     * Override in model to define token format regex.
     * Return null to skip format validation.
     */
    public static function tokenFormatPattern(): ?string
    {
        return null;
    }
}
