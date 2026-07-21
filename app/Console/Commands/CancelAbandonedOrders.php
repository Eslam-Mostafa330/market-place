<?php

namespace App\Console\Commands;

use App\Enums\CancellationReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Order\CancelOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelAbandonedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-abandoned {--minutes=30 : Age in minutes after which an unpaid card order is abandoned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancels card orders whose payment was never completed, releasing their stock, coupon and wallet balance.';

    /**
     * Cancel abandoned pending card orders.
     *
     * Reuses CancelOrderService so abandoned orders follow the same
     * cancellation flow as manual cancellations.
     */
    public function handle(CancelOrderService $cancelOrderService): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff  = now()->subMinutes($minutes);

        $cancelled = 0;
        $failed    = 0;

        Order::query()
            ->select('id')
            ->where('payment_method', PaymentMethod::VISA)
            ->where('payment_status', PaymentStatus::PENDING)
            ->where('order_status', OrderStatus::PENDING)
            ->where('created_at', '<', $cutoff)
            ->chunkById(100, function ($orders) use ($cancelOrderService, &$cancelled, &$failed) {
                foreach ($orders as $order) {
                    try {
                        $cancelOrderService->cancel(
                            orderId: $order->id,
                            reason: CancellationReason::PAYMENT_NOT_COMPLETED,
                            note: __('orders.payment_not_completed'),
                            cancelledBy: 'system',
                        );

                        $cancelled++;
                    } catch (\Throwable $exception) {
                        $failed++;

                        Log::error('Failed to cancel abandoned order.', [
                            'order_id' => $order->id,
                            'message'  => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Cancelled {$cancelled} abandoned order(s); {$failed} failed.");

        return self::SUCCESS;
    }
}
