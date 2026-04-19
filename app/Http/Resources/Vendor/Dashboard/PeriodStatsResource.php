<?php

namespace App\Http\Resources\Vendor\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_orders'        => (int)   $this->total_orders,
            'delivered_orders'    => (int)   $this->delivered_orders,
            'cancelled_orders'    => (int)   $this->cancelled_orders,
            'total_earned'        => (float) $this->total_earned,
            'total_commission'    => (float) $this->total_commission,
            'pending_payout'      => (float) $this->pending_payout,
            'average_order_value' => (float) $this->average_order_value,
            'cash_orders'         => (int)   $this->cash_orders,
            'cash_earned'         => (float) $this->cash_earned,
            'visa_orders'         => (int)   $this->visa_orders,
            'visa_earned'         => (float) $this->visa_earned,
        ];
    }
}
