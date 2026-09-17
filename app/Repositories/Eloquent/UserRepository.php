<?php
namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function findByPhone(string $phoneNumber): ?User
    {
        return User::where('phone_number', $phoneNumber)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    public function findOrCreateByPhone(string $phoneNumber, array $defaults = []): User
    {
        return User::firstOrCreate(['phone_number' => $phoneNumber], $defaults);
    }

    public function getById(int $id): ?User
    {
        return User::find($id);
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }
}   
