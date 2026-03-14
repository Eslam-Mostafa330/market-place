<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class BusinessCategoryController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        $businessCategories = DB::table('business_categories')
            ->select('id', 'name')
            ->orderBy('name', 'ASC')
            ->get();

        return $this->apiResponse($businessCategories);
    }
}
