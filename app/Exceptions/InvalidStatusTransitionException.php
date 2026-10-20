<?php

namespace App\Exceptions;

use Exception;

class InvalidStatusTransitionException extends Exception
{
    public function __construct(string $from, string $to, string $entity = 'inquiry')
    {
        parent::__construct("Cannot transition {$entity} from '{$from}' to '{$to}'.");
    }
}
