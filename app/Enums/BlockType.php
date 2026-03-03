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
            self::Braindump => 'Brain Dump',
            self::Habits => 'Daily Habits',
            self::Feed => 'RSS Feed',
            self::Notes => 'Notes',
            self::Plan => 'Plan',
            self::Custom => 'Custom',
        };
    }
}
