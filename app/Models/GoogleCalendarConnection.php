<?php

namespace App\Models;

use App\Contracts\ApiCredentialProvider;
use App\Models\Concerns\HasEncryptedCredentials;
use App\Observers\ApiCredentialObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \Carbon\Carbon|null $token_expires_at
 */
#[ObservedBy(ApiCredentialObserver::class)]
class GoogleCalendarConnection extends Model implements ApiCredentialProvider
{
    /** @use HasFactory<\Database\Factories\GoogleCalendarConnectionFactory> */
    use HasEncryptedCredentials, HasFactory;

    protected $fillable = [
        'google_id',
        'name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'calendar_ids',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'calendar_ids' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<RoutineBlock, $this> */
    public function routineBlocks(): HasMany
    {
        return $this->hasMany(RoutineBlock::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at === null || $this->token_expires_at->isPast();
    }

    /** @return string[] */
    public function getCredentialAttributes(): array
    {
        return ['access_token', 'refresh_token'];
    }

    public function getProviderName(): string
    {
        return 'google_calendar';
    }
}
