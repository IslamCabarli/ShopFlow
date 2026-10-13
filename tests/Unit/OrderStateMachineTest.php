<?php

    namespace Tests\Unit;

    use App\Exceptions\InvalidOrderTransitionException;
    use App\Models\Order;
    use App\Services\OrderStateMachine;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class OrderStateMachineTest extends TestCase
    {
        use RefreshDatabase;

        public function test_allows_valid_order_transition(): void
        {
            $order = Order::factory()->create(['status' => 'pending']);

            (new OrderStateMachine())->transitionOrderTo($order, 'confirmed');

            $this->assertEquals('confirmed', $order->fresh()->status);
        }

        public function test_rejects_skipping_states(): void
        {
            $order = Order::factory()->create(['status' => 'pending']);

            $this->expectException(InvalidOrderTransitionException::class);

            (new OrderStateMachine())->transitionOrderTo($order, 'delivered');
        }

        public function test_rejects_transition_from_delivered_to_pending(): void
        {
            $order = Order::factory()->create(['status' => 'delivered']);

            $this->expectException(InvalidOrderTransitionException::class);

            (new OrderStateMachine())->transitionOrderTo($order, 'pending');
        }

        public function test_delivered_order_has_no_further_transitions(): void
        {
            $order = Order::factory()->create(['status' => 'delivered']);

            $this->expectException(InvalidOrderTransitionException::class);

            (new OrderStateMachine())->transitionOrderTo($order, 'cancelled');
        }

        public function test_allows_valid_payment_transition(): void
        {
            $order = Order::factory()->create(['payment_status' => 'pending']);

            (new OrderStateMachine())->transitionPaymentTo($order, 'paid');

            $this->assertEquals('paid', $order->fresh()->payment_status);
        }

        public function test_rejects_refund_before_payment(): void
        {
            $order = Order::factory()->create(['payment_status' => 'pending']);

            $this->expectException(InvalidOrderTransitionException::class);

            (new OrderStateMachine())->transitionPaymentTo($order, 'refunded');
        }
    }
