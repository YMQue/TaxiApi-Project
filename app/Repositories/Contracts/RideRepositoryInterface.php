<?php
namespace App\Repositories\Contracts;
use App\Models\Rating;
use App\Models\Ride;

interface RideRepositoryInterface
{
    public function estimateFare(array $data);
    public function findForRider(int $rideId, int $rider_id): ?Ride;
    public function paginateForRider(int $rider_id, int $perPage = 15, ?string $status = null);
    public function createRide(int $riderId, array $data): Ride;
    public function rate(int $rideId, string $role, int $actorId, int $rating, ?string $comment = null): Rating;







    public function cancelRide(int $rideId, string $by, int $actorId, ?string $reason = null): Ride;





    public function acceptRide(int $rideId, int $driverId): Ride;
    public function rejectRide(int $rideId, int $driverId): void;
    public function markArrived(int $rideId, int $driverId): Ride;
    public function startRide(int $rideId, int $driverId): Ride;
    public function completeRide(int $rideId, int $driverId): Ride;





   
    
    
}