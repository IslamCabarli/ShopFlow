@component('mail::message')
    # Thank you for your order!

    Your order **{{ $order->order_number }}** has been received and is being processed.

    @component('mail::table')
        | Product | Qty | Unit Price | Subtotal |
        | --- | --- | --- | --- |
        @foreach ($order->orderItems as $item)
            | {{ $item->product_name }} | {{ $item->quantity }} | ${{ $item->unit_price }} | ${{ $item->subtotal }} |
        @endforeach
    @endcomponent

    **Total: ${{ $order->total }}**

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
