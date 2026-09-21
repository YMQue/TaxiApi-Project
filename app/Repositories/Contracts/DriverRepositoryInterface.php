<?php

namespace App\Repositories\Contracts;

use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;

interface DriverRepositoryInterface
{
    public function create(array $data): Driver;
    public function findByUserId(int $userId): ?Driver;
    public function findNearestAvailable(Ride $ride, array $excludedDriverIds = []): ?Driver;
    public function updateLocation(Driver $driver, float $lat, float $lng, ?float $bearing = null): Driver;

}
