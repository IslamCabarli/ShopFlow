<?php

    namespace App\Listeners;

    use App\Events\OrderCreated;
    use App\Jobs\GenerateInvoiceJob;
    use Illuminate\Contracts\Queue\ShouldQueue;

    class GenerateInvoiceListener implements ShouldQueue
    {
        public function handle(OrderCreated $event): void
        {
            GenerateInvoiceJob::dispatch($event->order->id);
        }
    }
