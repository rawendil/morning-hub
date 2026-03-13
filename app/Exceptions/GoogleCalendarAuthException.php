<?php

namespace App\Exceptions;

use RuntimeException;

class GoogleCalendarAuthException extends RuntimeException
{
    public function __construct(string $message = 'Google Calendar authentication failed. Please reconnect.')
    {
        parent::__construct($message);
    }
}
