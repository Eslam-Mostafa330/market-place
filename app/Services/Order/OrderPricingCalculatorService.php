<?php

namespace App\Services\Order;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\StoreBranch;

class OrderPricingCalculatorService
{
    /**
     * Calculates all pricing components for an order.
     *
     * This method aggregates item costs, applies delivery fees, processes any
     * coupon discounts, and computes the final payable total. It also determines
     * how revenue is distributed between the platform (commission), vendor, and rider.
     *
     * Business rules:
     * - Commission is applied only to the subtotal (products), not delivery or discounts.
     * - Vendor earnings are subtotal minus commission.
     * - Rider earnings equal the full delivery fee.
     *
     * @param array       $items          List of items (unit_price, quantity)
     * @param StoreBranch $branch         Source of delivery fee
     * @param float       $commissionRate Commission percentage at order time
     * @param Coupon|null $coupon         Optional applied coupon
     *
     * @return array Pricing breakdown including totals and earnings distribution
     */
    public function calculate(array $items, StoreBranch $branch, float $commissionRate, ?Coupon $coupon = null): array 
    {
        $subtotal    = $this->calculateSubtotal($items);
        $deliveryFee = (float) $branch->delivery_fee;
        $discount    = $this->calculateDiscount($coupon, $subtotal);
        $total       = round($subtotal + $deliveryFee - $discount, 2);

        $commissionAmount = round($subtotal * ($commissionRate / 100), 2);
        $vendorEarnings = round($subtotal - $commissionAmount, 2);
        $riderEarnings = $deliveryFee;

        return [
            'subtotal'          => $subtotal,
            'delivery_fee'      => $deliveryFee,
            'discount'          => $discount,
            'total'             => $total,
            'commission_rate'   => $commissionRate,
            'commission_amount' => $commissionAmount,
            'vendor_earnings'   => $vendorEarnings,
            'rider_earnings'    => $riderEarnings,
        ];
    }

    /**
     * Computes the subtotal of all order items.
     *
     * Each item's total is calculated as (unit_price × quantity),
     * and all items are summed together. The result is rounded
     * to 2 decimal places for currency consistency.
     *
     * @param array $items
     * @return float
     */
    private function calculateSubtotal(array $items): float
    {
        return round(
            array_sum(
                array_map(fn ($item) => $item['unit_price'] * $item['quantity'], $items)
            ),
            2
        );
    }

    /**
     * Determines the discount value based on the provided coupon.
     *
     * Supports:
     * - Fixed discount: direct deduction
     * - Percentage discount: calculated from subtotal with optional cap
     *
     * Discount can never exceed the subtotal (order total can't go negative).
     *
     * @param Coupon|null $coupon
     * @param float $subtotal
     * @return float
     */
    private function calculateDiscount(?Coupon $coupon, float $subtotal): float
    {
        if (! $coupon) return 0.0;
        
        $discount = match ($coupon->coupon_type) {
            CouponType::FIXED => (float) $coupon->value,
            CouponType::PERCENTAGE => $this->calculatePercentageDiscount($coupon, $subtotal),
        };

        return round(min($discount, $subtotal), 2);
    }

    /**
     * Calculates a percentage-based discount from the subtotal.
     *
     * Applies the coupon percentage and enforces a maximum discount
     * cap if defined on the coupon.
     *
     * @param Coupon $coupon
     * @param float $subtotal
     * @return float
     */
    private function calculatePercentageDiscount(Coupon $coupon, float $subtotal): float
    {
        $discount = $subtotal * ($coupon->value / 100);

        if (! empty($coupon->maximum_discount)) {
            $discount = min($discount, (float) $coupon->maximum_discount);
        }

        return $discount;
    }
}