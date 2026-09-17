<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RideAccepted implements ShouldBroadcast
{
    public function __construct(
        public Ride $ride
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('rider.' . $this->ride->rider_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'RideAccepted';
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'driver_id' => $this->ride->driver_id,
            'status' => $this->ride->status,
        ];
    }
}

