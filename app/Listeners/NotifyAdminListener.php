<?php

    namespace App\Listeners;

    use App\Events\OrderCreated;
    use App\Jobs\NotifyAdminJob;
    use Illuminate\Contracts\Queue\ShouldQueue;

    class NotifyAdminListener implements ShouldQueue
    {
        public function handle(OrderCreated $event): void
        {
            NotifyAdminJob::dispatch($event->order->id);
        }
    }
