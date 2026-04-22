<?php

namespace App\Http\Resources\Admin\Dashboard;

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
            'total_orders'           => (int)   $this->total_orders,
            'delivered_orders'       => (int)   $this->delivered_orders,
            'cancelled_orders'       => (int)   $this->cancelled_orders,
            'cash_orders'            => (int)   $this->cash_orders,
            'visa_orders'            => (int)   $this->visa_orders,
            'visa_earned'            => (float) $this->visa_earned,
            'platform_commission'    => (float) $this->platform_commission,
            'average_order_value'    => (float) $this->average_order_value,
            'pending_vendor_payouts' => (float) $this->pending_vendor_payouts,
            'pending_rider_payouts'  => (float) $this->pending_rider_payouts,
            'stores_count'           => (int)   $this->stores_count,
            'active_coupons_count'   => (int)   $this->active_coupons_count,
            'admins_count'           => (int)   $this->admins_count,
            'vendors_count'          => (int)   $this->vendors_count,
            'customers_count'        => (int)   $this->customers_count,
            'riders_count'           => (int)   $this->riders_count,
        ];
    }
}