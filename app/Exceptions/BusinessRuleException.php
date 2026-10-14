<?php

namespace App\Exceptions;

use DomainException;


class BusinessRuleException extends DomainException
{
    public function __construct(string $message, public int $status = 422)
    {
        parent::__construct($message);
    }
}
