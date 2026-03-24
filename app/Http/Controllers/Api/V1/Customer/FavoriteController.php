<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Customer\Favorite\FavoriteProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class FavoriteController extends BaseApiController
{
    /**
     * Get the authenticated customer's favorite products.
     *
     * @param Request $request
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $favorites = $request->user()
            ->favoriteProducts()
            ->select('products.id', 'products.name', 'products.slug', 'products.image', 'products.price', 'products.sale_price')
            ->latest('favorites.created_at')
            ->useFilters()
            ->dynamicPaginate();

        return FavoriteProductResource::collection($favorites);
    }

    /**
     * Remove a product from the authenticated customer's wishlist.
     *
     * @param string $productId
     */
    public function destroy(string $productId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $deleted = DB::table('favorites')
            ->where('customer_id', $userId)
            ->where('product_id', $productId)
            ->delete() > 0;

        abort_if(! $deleted, 404);
        return $this->apiResponseDeleted();
    }
}