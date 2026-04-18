<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\DefineStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Public\Concerns\ResolvesAuthCustomer;
use App\Http\Resources\Public\StoreProduct\StoreProductListResource;
use App\Http\Resources\Public\StoreProduct\StoreProductResource;
use App\Models\Product;
use App\Models\Store;
use App\Services\Product\ProductViewService;

class StoreProductController extends BaseApiController
{
    use ResolvesAuthCustomer;

    public function __construct(private readonly ProductViewService $viewService) {}

    /**
     * Display a list of active products for a given store.
     *
     * Includes an "is_favorite" flag if the authenticated customer has added the product to their favorites.
     */
    public function index(Store $store)
    {
        $products = $store->products()
            ->select('products.id', 'products.name', 'products.slug', 'products.image', 'products.price', 'products.sale_price')
            ->active()
            ->useFilters()
            ->withFavoriteStatus($this->authCustomer())
            ->dynamicPaginate();

        return StoreProductListResource::collection($products);
    }

    /**
     * Display the product details.
     *
     * Includes an "is_favorite" flag if the authenticated customer has added
     * the product to their favorites. Also returns related products from the
     * same category, each including their favorite status.
     * Record this product as viewed for the authenticated customer
     */
    public function show(Store $store, Product $product)
    {
        abort_if($product->status !== DefineStatus::ACTIVE, 404);
        $customer = $this->authCustomer();
        $product->loadFavoriteStatus($customer);
        $this->viewService->record($product);
        
        $product->setRelation('relatedProducts', $store->products()
            ->select('id', 'name', 'slug', 'image', 'price', 'sale_price')
            ->where('product_category_id', $product->product_category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->withFavoriteStatus($customer)
            ->limit(8)
            ->get()
        );

        return new StoreProductResource($product);
    }
}