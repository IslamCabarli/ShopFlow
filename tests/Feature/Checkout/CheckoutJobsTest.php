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

    public function test_jobs_have_retry_and_backoff_configured(): void
    {
        $job = new GenerateInvoiceJob(1);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([10, 30], $job->backoff());
    }

    public function test_duplicate_dispatch_for_same_order_is_deduplicated(): void
    {
        $order = $this->createOrderWithItems();

        SendOrderConfirmationJob::dispatch($order->id);
        SendOrderConfirmationJob::dispatch($order->id);

        $this->assertEquals(1, DB::table('jobs')->count());
    }

    private function checkoutAsNewUser(): User
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'discount_price' => null]);
        Inventory::factory()->create(['product_id' => $product->id, 'quantity' => 10]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/checkout', [
            'shipping_name' => 'Test',
            'shipping_address' => 'Addr',
            'shipping_city' => 'Baku',
            'shipping_country' => 'Azerbaijan',
        ]);

        return $user;
    }

    private function createOrderWithItems(): Order
    {
        $user = $this->checkoutAsNewUser();

        return Order::where('user_id', $user->id)->latest()->first();
    }
}
