<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Public\Favorite\ToggleFavoriteRequest;
use App\Jobs\CustomerPreference\RefreshCustomerPreferences;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FavoriteController extends BaseApiController
{
    /**
     * Toggle product in user's favorites (wishlist).
     *
     * - If the product already exists in favorites then it will be removed
     * - If it does not exist then it will be added
     */
    public function toggle(ToggleFavoriteRequest $request)
    {
        $user = $request->user();
        $productId = $request->validated()['product_id'];

        if ($this->removeFromFavorites($user->id, $productId)) {
            RefreshCustomerPreferences::throttledDispatch($user->id, 'favorite');
            return $this->apiResponseDeleted($productId);
        }

        $this->addToFavorites($user->id, $productId);
        RefreshCustomerPreferences::throttledDispatch($user->id, 'favorite');
        return $this->apiResponseStored($productId);
    }

    /**
     * Remove a product from user's favorites.
     *
     * @return bool True if the product was removed, false if it was not in favorites
     */
    private function removeFromFavorites(string $userId, string $productId): bool
    {
        return DB::table('favorites')
            ->where('customer_id', $userId)
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    /**
     * Add a product to user's favorites.
     *
     * Inserts a new favorite record with UUID.
     */
    private function addToFavorites(string $userId, string $productId): void
    {
        DB::table('favorites')->insert([
            'id'          => Str::uuid(),
            'customer_id' => $userId,
            'product_id'  => $productId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}