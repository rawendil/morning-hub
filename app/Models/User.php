<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'google_avatar',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * The start of the user's current local day, falling back to UTC
     * when no valid timezone is stored on the account.
     */
    public function localDate(): CarbonImmutable
    {
        return CarbonImmutable::now($this->resolvedTimezone())->startOfDay();
    }

    public function resolvedTimezone(): string
    {
        $timezone = $this->timezone;

        if ($timezone === null || ! in_array($timezone, timezone_identifiers_list(), true)) {
            return 'UTC';
        }

        return $timezone;
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function hasGoogleLinked(): bool
    {
        return $this->google_id !== null;
    }

    /** @return HasMany<ClickUpConnection, $this> */
    public function clickUpConnections(): HasMany
    {
        return $this->hasMany(ClickUpConnection::class);
    }

    /** @return HasMany<RoutineBlock, $this> */
    public function routineBlocks(): HasMany
    {
        return $this->hasMany(RoutineBlock::class);
    }

    /** @return HasOne<TodaysTasksConfig, $this> */
    public function todaysTasksConfig(): HasOne
    {
        return $this->hasOne(TodaysTasksConfig::class);
    }

    /** @return HasOne<GoogleCalendarConnection, $this> */
    public function googleCalendarConnection(): HasOne
    {
        return $this->hasOne(GoogleCalendarConnection::class);
    }
}
