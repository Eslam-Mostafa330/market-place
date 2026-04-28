<?php

namespace App\Services\Rider;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RiderService
{
    /**
     * Create a new rider with their profile in a single transaction.
     *
     * @param  array $data Validated input containing user and profile fields.
     * @return User        The created user with riderProfile relation set.
     */
    public function createRider(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user    = User::create($this->getUserData($data));
            $profile = $user->riderProfile()->create($this->getProfileData($data));

            return $user->setRelation('riderProfile', $profile);
        });
    }

    /**
     * Update an existing rider and their profile in a single transaction.
     *
     * @param  User  $rider The rider to update.
     * @param  array $data  Validated input containing user and profile fields.
     * @return User         The updated user with riderProfile relation set.
     */
    public function updateRider(User $rider, array $data): User
    {
        return DB::transaction(function () use ($data, $rider) {
            $rider->update($this->getUserData($data));

            $profile = $rider->riderProfile()->updateOrCreate(
                [],
                $this->getProfileData($data)
            );

            return $rider->setRelation('riderProfile', $profile);
        });
    }

    /**
     * Extract and prepare user data for creating or updating a rider.
     */
    private function getUserData(array $data): array
    {
        return [
            ...Arr::only($data, ['name', 'email', 'phone', 'password']),
            'role' => UserRole::RIDER,
        ];
    }

    /**
     * Extract and prepare rider profile data for creating or updating a rider profile.
     */
    private function getProfileData(array $data): array
    {
        return Arr::only($data, ['license_number', 'license_expiry', 'vehicle_type', 'vehicle_number']);
    }
}