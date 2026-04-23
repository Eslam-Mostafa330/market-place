<?php

namespace App\Http\Resources\Rider\Dashboard;

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
            'total_orders'                 => (int)   $this->total_orders,
            'delivered_orders'             => (int)   $this->delivered_orders,
            'total_earned'                 => (float) $this->total_earned,
            'average_earning_per_delivery' => (float) $this->average_earning_per_delivery,
            'cash_orders'                  => (int)   $this->cash_orders,
            'cash_earned'                  => (float) $this->cash_earned,
            'visa_orders'                  => (int)   $this->visa_orders,
            'visa_earned'                  => (float) $this->visa_earned,
        ];
    }
}