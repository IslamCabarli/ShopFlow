<?php

namespace Tests\Feature\Checkout;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\NotifyAdminJob;
use Illuminate\Support\Facades\Queue;

class CheckoutJobsTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_checkout_dispatches_all_three_jobs(): void
    {
        Queue::fake();

        $this->checkoutAsNewUser();

        Queue::assertPushed(SendOrderConfirmationJob::class);
        Queue::assertPushed(GenerateInvoiceJob::class);
        Queue::assertPushed(NotifyAdminJob::class);
    }
}
