<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\DefineStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Customer\Preference\PreferenceResource;
use App\Models\CustomerProfile;
use App\Models\Product;

class PreferencesController extends BaseApiController
{
    public function __invoke()
    {
        $profile = CustomerProfile::where('user_id', auth()->id())->first();

        if (! $profile || empty($profile->preferences)) {
            return;
        }

        $productIds = $profile->preferences;

        $products = Product::whereIn('id', $productIds)
            ->where('status', DefineStatus::ACTIVE)
            ->select('id', 'name', 'price', 'sale_price', 'image')
            ->get()
            ->sortBy(fn ($product) => array_search($product->id, $productIds))
            ->values();

        return PreferenceResource::collection($products);
    }
}