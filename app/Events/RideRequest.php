<?php
namespace App\Events;

use App\Models\Driver;
use App\Models\Ride;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;


class RideRequest implements ShouldBroadcast
{
     private SettingRepositoryInterface $setting;
    public string $expiresAt;

    public function __construct(
        public Ride $ride,
        public Driver $driver,
    ) {
        $timeout = (int) app(SettingRepositoryInterface::class)
        ->get('ride_request_timeout_sec');

    $this->expiresAt = now()->addSeconds($timeout)->toIso8601String();
    }

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
        ];
    }
}
