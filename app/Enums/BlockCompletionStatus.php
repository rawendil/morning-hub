<?php

namespace App\Enums;

enum BlockCompletionStatus: string
{
    case Completed = 'completed';
    case Skipped = 'skipped';
}
