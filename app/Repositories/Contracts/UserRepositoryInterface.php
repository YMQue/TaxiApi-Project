<?php

namespace App\Repositories\Contracts;

use App\Models\Ride;
use App\Models\User;

interface UserRepositoryInterface
{
    public function findByPhone(string $phoneNumber): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function findOrCreateByPhone(string $phoneNumber, array $defaults = []): User;
    public function getById(int $id): ?User;
    public function delete(User $user): bool;
    
   
}


