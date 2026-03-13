<?php

namespace App\Enums;

enum BlockType: string
{
    case Clickup = 'clickup';
    case Braindump = 'braindump';
    case Notes = 'notes';
    case Plan = 'plan';
    case Habits = 'habits';
    case Feed = 'feed';
    case Custom = 'custom';
    case GoogleCalendar = 'google_calendar';

    public function label(): string
    {
        return match ($this) {
            self::Clickup => 'ClickUp',
            self::Braindump => __('Zrzut myśli'),
            self::Habits => __('Codzienne nawyki'),
            self::Feed => __('Kanał RSS'),
            self::Notes => __('Notatki'),
            self::Plan => __('Plan'),
            self::Custom => __('Własny'),
            self::GoogleCalendar => __('Google Calendar'),
        };
    }
}
