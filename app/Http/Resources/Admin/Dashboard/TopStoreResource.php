<?php

namespace App\Http\Resources\Admin\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopStoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'store_id'        => $this->store_id,
            'store_name'      => $this->store_name,
            'orders_count'    => (int)   $this->orders_count,
            'total_revenue'   => (float) $this->total_revenue,
            'vendor_earnings' => (float) $this->vendor_earnings,
        ];
    }
}