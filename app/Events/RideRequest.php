<?php
namespace App\Events;

use App\Models\Driver;
use App\Models\Ride;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;


class RideRequest implements ShouldBroadcast
{
    public function __construct(
        public Ride $ride,
        public Driver $driver
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('driver.' . $this->driver->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'RideRequested';
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'pickup' => [
                'lat' => $this->ride->pickup_lat,
                'lng' => $this->ride->pickup_lng,
                'address' => $this->ride->pickup_address,
            ],
            'dropoff' => [
                'lat' => $this->ride->drop_lat,
                'lng' => $this->ride->drop_lng,
                'address' => $this->ride->drop_address,
            ],
            'estimated_fare' => $this->ride->estimated_fare,
            'expires_at' => now()->addSeconds(30)
               
        ];
    }
}
