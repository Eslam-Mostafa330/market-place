<?php

namespace App\Http\Resources\Rider\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyEarningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'month'        => $this->month,
            'earned'       => (float) $this->earned,
            'orders_count' => (int) $this->orders_count,
        ];
    }
}
