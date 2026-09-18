<?php

namespace App\Events;

use App\Models\Driver;
use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverArrived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Ride $ride,
        public Driver $driver)
    {

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
         return [
            new PrivateChannel('rider.' . $this->ride->rider_id),
            new PrivateChannel('driver.' . $this->driver->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver.arrived';
    }

     public function broadcastWith(): array
    {
        return [
            'ride_id'   => $this->ride->id,
            'driver_id' => $this->driver->id,
            'message'   => 'Driver Arrived', // "Arrival notification"
            'arrived_at' => now()->toDateTimeString(),
        ];
    }
}
