<?php

namespace App\Http\Resources\Customer\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'order_number'      => $this->order_number,
            'order_status'      => $this->order_status,
            'payment_status'    => $this->payment_status,
            'total'             => $this->total,
            'created_at'        => $this->created_at,
            'store_name'        => $this->store->name,
            'store_branch_name' => $this->storeBranch->name,
            'is_reviewed'       => (bool) $this->review_exists,
        ];
    }
}