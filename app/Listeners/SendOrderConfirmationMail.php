<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Mail\OrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationMail implements ShouldQueue
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        Mail::to($order->client_mail)->send(new OrderConfirmationMail($order));
    }
}
