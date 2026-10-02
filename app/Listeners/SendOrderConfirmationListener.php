<?php

    namespace App\Listeners;

    use App\Events\OrderCreated;
    use App\Jobs\SendOrderConfirmationJob;
    use Illuminate\Contracts\Queue\ShouldQueue;

    class SendOrderConfirmationListener implements ShouldQueue
    {
        public function handle(OrderCreated $event): void
        {
            SendOrderConfirmationJob::dispatch($event->order->id);
        }
    }
