<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
 
    public Ride $ride;
    public string $cancelledBy;
 
    public function __construct(Ride $ride, string $cancelledBy)
    {
        $this->ride = $ride;
        $this->cancelledBy = $cancelledBy;
    }
 
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ride.{$this->ride->id}"),
        ];
    }
 
    public function broadcastAs(): string
    {
        return 'ride.cancelled';
    }
 
    public function broadcastWith(): array
    {
        return [
            'ride_id'       => $this->ride->id,
            'cancelled_by'  => $this->cancelledBy,
            'cancel_reason' => $this->ride->cancel_reason,
            'cancelled_at'  => $this->ride->cancelled_at,
        ];
    }
}
