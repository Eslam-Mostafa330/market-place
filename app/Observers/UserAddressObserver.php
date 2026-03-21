<?php

namespace App\Observers;

use App\Enums\BooleanStatus;
use App\Models\UserAddress;

class UserAddressObserver
{
    /**
     * Set the user_id from the authenticated user and enforce
     * the first address to always be default before creating.
     */
    public function creating(UserAddress $address): void
    {
        $address->user_id = auth()->id();
        $isFirstAddress = UserAddress::where('user_id', $address->user_id)->doesntExist();

        if ($isFirstAddress) {
            $address->is_default = BooleanStatus::YES;
        }
    }

    /**
     * If the new address is default,
     * demote all other addresses for this user to not default.
     */
    public function created(UserAddress $address): void
    {
        if ($address->is_default === BooleanStatus::YES) {
            $this->demoteOtherDefaults($address);
        }
    }

    /**
     * Strip is_default from any update to prevent tampering.
     * Default status is managed exclusively via the setDefault endpoint.
     */
    public function updating(UserAddress $address): void
    {
        $currentDefault = $address->getOriginal('is_default');
        $address->offsetUnset('is_default');
        $address->is_default = $currentDefault;
    }

    /**
     * Handle the UserAddress "updated" event.
     */
    public function updated(UserAddress $userAddress): void
    {
        //
    }

    /**
     * Handle the UserAddress "deleted" event.
     */
    public function deleted(UserAddress $userAddress): void
    {
        //
    }

    /**
     * Handle the UserAddress "restored" event.
     */
    public function restored(UserAddress $userAddress): void
    {
        //
    }

    /**
     * Handle the UserAddress "force deleted" event.
     */
    public function forceDeleted(UserAddress $userAddress): void
    {
        //
    }

    /**
     * Demote all other addresses for the same user to not default.
     */
    private function demoteOtherDefaults(UserAddress $address): void
    {
        UserAddress::where('user_id', $address->user_id)
            ->where('id', '!=', $address->id)
            ->where('is_default', BooleanStatus::YES)
            ->update(['is_default' => BooleanStatus::NO]);
    }
}