<?php

namespace App\Exceptions;

use RuntimeException;

class RatingException extends RuntimeException
{
    public static function notFound(): self
    {
        return new self('Ride not found', 404);
    }

    public static function notCompleted(): self
    {
        return new self('Cannot Make rating before complete the ride', 422);
    }

    public static function alreadyRated(): self
    {
        return new self('This ride is already rated', 409);
    }
}