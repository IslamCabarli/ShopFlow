<?php

    namespace App\Jobs;

    use App\Models\Order;
    use Illuminate\Bus\Queueable;
    use Illuminate\Contracts\Queue\ShouldBeUnique;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Foundation\Bus\Dispatchable;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Support\Facades\Log;
    use Throwable;

    class GenerateInvoiceJob implements ShouldQueue, ShouldBeUnique
    {
        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;

        public int $uniqueFor = 3600;

        public function __construct(public int $orderId)
        {
        }

        public function uniqueId(): string
        {
            return (string) $this->orderId;
        }

        public function backoff(): array
        {
            return [10, 30];
        }

        public function handle(): void
        {
            $order = Order::with('orderItems')->findOrFail($this->orderId);

            // Placeholder for real PDF invoice generation later.
            Log::info("Invoice generated for order {$order->order_number}", [
                'order_id' => $order->id,
                'total' => $order->total,
            ]);
        }

        public function failed(Throwable $exception): void
        {
            Log::error("Failed to generate invoice for order {$this->orderId}: {$exception->getMessage()}");
        }
    }
