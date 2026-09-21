<?php

use App\Models\Ride;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('driver.{driverId}', function ($user, int $driverId) {
    return $user->driver && (int) $user->driver->id === $driverId;
});

Broadcast::channel('ride.{rideId}', function ($user, int $rideId) {
    $ride = Ride::find($rideId);
 
  return $ride && (
        $ride->rider_id === $user->id ||
        optional($user->driver)->id === $ride->driver_id
    );
});
