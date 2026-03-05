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

    public function label(): string
    {
        return match ($this) {
            self::Clickup => 'ClickUp',
            self::Braindump => 'Zrzut myśli',
            self::Habits => 'Codzienne nawyki',
            self::Feed => 'Kanał RSS',
            self::Notes => 'Notatki',
            self::Plan => 'Plan',
            self::Custom => 'Własny',
        };
    }
}
