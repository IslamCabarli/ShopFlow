<?php

    namespace App\Jobs;

    use App\Mail\OrderConfirmationMail;
    use App\Models\Order;
    use Illuminate\Bus\Queueable;
    use Illuminate\Contracts\Queue\ShouldBeUnique;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Foundation\Bus\Dispatchable;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Mail;
    use Throwable;

    class SendOrderConfirmationJob implements ShouldQueue, ShouldBeUnique
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
            $order = Order::with(['user', 'orderItems'])->findOrFail($this->orderId);

            Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
        }

        public function failed(Throwable $exception): void
        {
            Log::error("Failed to send order confirmation for order {$this->orderId}: {$exception->getMessage()}");
        }
    }
