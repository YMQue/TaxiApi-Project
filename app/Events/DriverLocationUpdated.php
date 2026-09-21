<?php

namespace App\Events;

use App\Models\Driver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct( 
        public float $current_lat,
        public float $current_lng,
        public float $bearing ,
        public Driver $driver,
        )
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('driver.' . $this->driver->id),
        ];
    }

     public function broadcastAs(): string
    {
        return 'DriverLocationUpdated';
    }

     public function broadcastWith(): array
    {
        return [
            'curent_lat' => $this->current_lat,
            'current_lng' => $this->current_lng,
            'bearing'   => $this->bearing,
            'timestamp' => now(),
        ];
    }
}
