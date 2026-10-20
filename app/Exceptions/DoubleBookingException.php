<?php

namespace App\Exceptions;

use Exception;

class DoubleBookingException extends Exception
{
    public function __construct(string $message = 'This time slot is already booked for the selected property.')
    {
        parent::__construct($message);
    }
}
