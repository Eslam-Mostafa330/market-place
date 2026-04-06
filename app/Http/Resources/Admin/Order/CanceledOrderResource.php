<?php

namespace App\Http\Resources\Admin\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CanceledOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'order_status'        => $this->order_status,
            'cancelled_by'        => $this->cancelled_by,
            'cancellation_reason' => $this->cancellation_reason,
            'cancellation_note'   => $this->cancellation_note,
        ];
    }
}
