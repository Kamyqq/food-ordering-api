<x-mail::message>
# Your order has been cancelled

We are sorry, but order #{{ $order->id }} has been cancelled.

We guarantee that the funds in the amount of **{{ number_format($order->total_price / 100, 2) }} PLN** have already been refunded to your account via the Stripe system. The transfer should reach your card within 1-3 business days.

If you have any questions, please contact our customer support.

Best regards,<br>
The Pizzeria Team
</x-mail::message>
