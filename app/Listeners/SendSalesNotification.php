<?php

namespace App\Listeners;

use App\Events\SalesOrderCompleted;
use App\Jobs\SendSalesOrderNotification;

class SendSalesNotification
{
    public function handle(SalesOrderCompleted $event): void
    {
        // Dispatch job notifikasi ke queue setelah transaksi SO selesai (PRD 6.5 & 9.2)
        SendSalesOrderNotification::dispatch(
            $event->salesOrder->id,
            $event->salesOrder->order_number
        );
    }
}

