<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Mail\OrderCancelledMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderCancellationMail implements ShouldQueue
{
    public function handle(OrderCancelled $event): void
    {
        Mail::to($event->order->client_mail)->send(new OrderCancelledMail($event->order));
    }
}
