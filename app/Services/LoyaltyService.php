<?php

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class LoyaltyService
{
    const POINT_VALUE = 0.01;

    /**
     * Award loyalty points after order delivery.
     *
     * Formula: points = round( (subtotal - product_discount - wallet_discount) × points_per_unit )
     * The multiplier `points_per_unit` comes from the application settings (`Setting::loyaltyPoints()`).
     * Rounded to the nearest integer.
     */
    public function awardPoints(Order $order): void
    {
        $pointsPerUnit = cache()->rememberForever('loyalty_points', fn () => (int) Setting::loyaltyPoints());

        $actualPaid = $order->subtotal - $order->discount - $order->wallet_discount;

        $pointsToAward = (int) round($actualPaid * $pointsPerUnit);

        if ($pointsToAward <= 0) {
            return;
        }

        CustomerProfile::where('user_id', $order->customer_id)->increment('loyalty_points', $pointsToAward);
    }

    /**
     * Redeem loyalty points as wallet balance.
     *
     * Converts points to wallet credit at fixed rate:
     * 1 point = $0.01 wallet balance.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    public function redeemPoints(CustomerProfile $profile, int $points): CustomerProfile
    {
        if ($points <= 0) {
            throw new UnprocessableEntityHttpException(__('loyalty.greater_than_zero'));
        }

        $walletCredit = round($points * self::POINT_VALUE, 2);

        $updated = CustomerProfile::where('id', $profile->id)
            ->where('loyalty_points', '>=', $points)
            ->update([
                'loyalty_points' => DB::raw("loyalty_points - {$points}"),
                'wallet_balance' => DB::raw("wallet_balance + {$walletCredit}"),
            ]);

        if ($updated === 0) {
            throw new UnprocessableEntityHttpException(__('loyalty.insufficient_balance'));
        }

        $profile->loyalty_points -= $points;
        $profile->wallet_balance += $walletCredit;

        return $profile;
    }

    /**
     * Calculate maximum wallet discount for an order.
     *
     * Wallet can cover at most 50% of order total.
     * Returns the actual discount amount.
     */
    public function calculateWalletDiscount(float $walletBalance, float $orderTotal): float
    {
        $maxDiscount = round($orderTotal * 0.50, 2);

        return min($walletBalance, $maxDiscount);
    }

    /**
     * Deduct wallet discount from customer balance after order placed.
     *
     * Only called if customer chose to use wallet at checkout.
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    public function deductWalletBalance(CustomerProfile $profile, float $amount): void
    {
        if ($amount <= 0) {
            throw new UnprocessableEntityHttpException(__('loyalty.greater_than_zero'));
        }

        $updated = CustomerProfile::where('id', $profile->id)
            ->where('wallet_balance', '>=', $amount)
            ->update([
                'wallet_balance' => DB::raw("wallet_balance - {$amount}"),
            ]);

        if ($updated === 0) {
            throw new UnprocessableEntityHttpException(__('loyalty.insufficient_balance'));
        }
    }
}