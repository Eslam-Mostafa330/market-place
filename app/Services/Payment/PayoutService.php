<?php

namespace App\Services\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PayoutStatus;
use App\Models\Order;
use App\Models\RiderPayout;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Arr;

class PayoutService
{
    /**
     * Create a pending payout record for the rider after delivery.
     *
     * Only VISA orders need a payout record, as COD orders are paid directly to the rider.
     * This is called automatically when order status moves to delivered.
     */
    public function createPayoutIfNeeded(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::VISA) {
            return;
        }

        RiderPayout::query()->insertOrIgnore([
            'id'         => Str::uuid(),
            'rider_id'   => $order->rider_id,
            'order_id'   => $order->id,
            'amount'     => $order->rider_earnings,
            'status'     => PayoutStatus::PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Mark a payout as completed by admin.
     *
     * Validates the payout is not already completed, then updates status and payment details.
     *
     * @param RiderPayout $payout The payout to complete.
     * @param array       $data   Validated input containing payout_method, reference, notes, and payment_proof.
     *
     * @return RiderPayout The updated payout.
     */
    public function completePayout(RiderPayout $payout, array $data): RiderPayout
    {
        $this->validatePayoutCanBeCompleted($payout);

        $payout->update([
            ...Arr::only($data, ['payout_method', 'reference', 'notes', 'payout_proof']),
            'status'        => PayoutStatus::COMPLETED,
            'processed_by'  => auth()->id(),
            'paid_at'       => now(),
        ]);

        return $payout;
    }

    /**
     * Update payout details for a completed payout.
     *
     * Validates the payout is already completed.
     *
     * @param RiderPayout $payout The payout to update.
     * @param array       $data   Validated input containing payout_method, reference, notes, and payment_proof.
     *
     * @return RiderPayout The updated payout.
     */
    public function updatePayoutDetails(RiderPayout $payout, array $data): RiderPayout
    {
        $this->validatePayoutNotCompleted($payout);

        $payout->update([
            Arr::only($data, ['payout_method', 'reference', 'notes', 'payout_proof']),
            'updated_by' => auth()->id(),
        ]);

        return $payout;
    }

    /**
     * Validate that the payout can be completed (i.e., it is not already completed).
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function validatePayoutCanBeCompleted(RiderPayout $payout): void
    {
        if ($payout->status === PayoutStatus::COMPLETED) {
            throw new UnprocessableEntityHttpException(__('payment.payout.already_completed'));
        }
    }

    /**
     * Validate that the payout can be edited (i.e., it is already completed).
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function validatePayoutNotCompleted(RiderPayout $payout): void
    {
        if ($payout->status !== PayoutStatus::COMPLETED) {
            throw new UnprocessableEntityHttpException(__('payment.payout.not_completed'));
        }
    }
}