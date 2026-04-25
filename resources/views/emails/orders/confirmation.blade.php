<x-mail::message>
# Hello!

Thank you for placing and paying for your order at our Pizza place.
We have received confirmation from the payment system. Your food has just been sent to the kitchen!

**Order number:** #{{ $order->id }}

**Delivery address:** {{ $order->client_address }}

### Order summary:

<x-mail::table>
    | Item | Qty | Price |
    | :--- | :--- | :--- |
    @foreach($order->orderItems as $item)
        | {{ $item->dish->name }} | {{ $item->quantity }}x | {{ number_format($item->dish->price / 100, 2) }} PLN |
    @endforeach
</x-mail::table>

**Total amount paid:** {{ number_format($order->total_price / 100, 2) }} PLN

Please expect the courier shortly. Enjoy your meal!

Best regards,<br>
Head Chef and the Pizzeria Team
</x-mail::message>
