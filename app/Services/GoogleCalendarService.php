<?php

namespace App\Services;

use App\Exceptions\GoogleCalendarAuthException;
use App\Models\GoogleCalendarConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleCalendarService
{
    private const string BASE_URL = 'https://www.googleapis.com/calendar/v3';

    public function __construct(
        private GoogleCalendarConnection $connection,
    ) {}

    public function testConnection(): bool
    {
        try {
            return $this->client()
                ->get(self::BASE_URL.'/users/me/calendarList', ['maxResults' => 1])
                ->successful();
        } catch (GoogleCalendarAuthException $e) {
            throw $e;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, array{id: string, name: string, color: string}>
     */
    public function listCalendars(): array
    {
        $response = $this->client()
            ->get(self::BASE_URL.'/users/me/calendarList')
            ->json();

        return array_map(fn (array $calendar) => [
            'id' => $calendar['id'],
            'name' => $calendar['summary'] ?? $calendar['id'],
            'color' => $calendar['backgroundColor'] ?? '#4285f4',
        ], $response['items'] ?? []);
    }

    /**
     * @param  string[]  $calendarIds
     * @return array<int, array{id: string, title: string, start: string, end: string, all_day: bool, location: ?string, calendar_color: string, calendar_name: string}>
     */
    public function listEventsForDate(string $date, array $calendarIds, string $timezone): array
    {
        $timeMin = Carbon::parse($date, $timezone)->startOfDay()->toRfc3339String();
        $timeMax = Carbon::parse($date, $timezone)->endOfDay()->toRfc3339String();

        $allEvents = [];

        foreach ($calendarIds as $calendarId) {
            $response = $this->client()
                ->get(self::BASE_URL.'/calendars/'.urlencode($calendarId).'/events', [
                    'timeMin' => $timeMin,
                    'timeMax' => $timeMax,
                    'timeZone' => $timezone,
                    'singleEvents' => 'true',
                    'orderBy' => 'startTime',
                    'maxResults' => 50,
                ])
                ->json();

            $calendarName = $response['summary'] ?? $calendarId;
            $calendarColor = $response['backgroundColor'] ?? '#4285f4';

            foreach ($response['items'] ?? [] as $event) {
                $allEvents[] = $this->mapEvent($event, $calendarColor, $calendarName);
            }
        }

        usort($allEvents, function (array $a, array $b) {
            if ($a['all_day'] !== $b['all_day']) {
                return $a['all_day'] ? -1 : 1;
            }

            return $a['start'] <=> $b['start'];
        });

        return $allEvents;
    }

    public function refreshTokenIfNeeded(): void
    {
        if (! $this->connection->isTokenExpired()) {
            return;
        }

        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver('google');
            $newToken = $driver->refreshToken($this->connection->refresh_token);

            $this->connection->update([
                'access_token' => $newToken->token,
                'refresh_token' => $newToken->refreshToken ?: $this->connection->refresh_token,
                'token_expires_at' => now()->addSeconds($newToken->expiresIn),
            ]);
        } catch (\Throwable $e) {
            Log::channel('security')->warning('Google Calendar token refresh failed', [
                'user_id' => $this->connection->user_id,
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new GoogleCalendarAuthException;
        }
    }

    private function client(): PendingRequest
    {
        $this->refreshTokenIfNeeded();

        return Http::withToken($this->connection->access_token)
            ->accept('application/json')
            ->throw();
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array{id: string, title: string, start: string, end: string, all_day: bool, location: ?string, calendar_color: string, calendar_name: string}
     */
    private function mapEvent(array $event, string $calendarColor, string $calendarName): array
    {
        $allDay = isset($event['start']['date']);

        return [
            'id' => $event['id'],
            'title' => $event['summary'] ?? '(No title)',
            'start' => $allDay ? $event['start']['date'] : $event['start']['dateTime'],
            'end' => $allDay ? $event['end']['date'] : $event['end']['dateTime'],
            'all_day' => $allDay,
            'location' => $event['location'] ?? null,
            'calendar_color' => $calendarColor,
            'calendar_name' => $calendarName,
        ];
    }

    /**
     * Fetch today's events for a dashboard block.
     *
     * @return array{events: array<int, array<string, mixed>>, error: string|null}
     */
    public function getEventsForDashboard(): array
    {
        try {
            $calendarIds = $this->connection->calendar_ids ?? ['primary'];
            $timezone = config('app.timezone', 'UTC');

            return [
                'events' => $this->listEventsForDate(now()->toDateString(), $calendarIds, $timezone),
                'error' => null,
            ];
        } catch (GoogleCalendarAuthException) {
            return [
                'events' => [],
                'error' => 'google_calendar_auth_expired',
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'events' => [],
                'error' => 'google_calendar_fetch_failed',
            ];
        }
    }
}
