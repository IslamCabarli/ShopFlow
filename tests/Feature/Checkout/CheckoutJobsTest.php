<?php

namespace Tests\Feature\Checkout;

use App\Mail\OrderConfirmationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\NotifyAdminJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    public function test_send_order_confirmation_job_sends_mail(): void
    {
        Mail::fake();

        $order = $this->createOrderWithItems();

        (new SendOrderConfirmationJob($order->id))->handle();

        Mail::assertSent(
            OrderConfirmationMail::class,
            fn ($mail) => $mail->order->id === $order->id
        );
    }
    public function test_job_throws_for_nonexistent_order(): void
    {
        $this->expectException(ModelNotFoundException::class);

        (new SendOrderConfirmationJob(999999))->handle();
    }
}
