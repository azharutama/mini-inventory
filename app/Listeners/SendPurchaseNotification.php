<?php

namespace App\Listeners;

use App\Events\PurchaseOrderReceived;
use App\Jobs\SendPurchaseOrderNotification;

class SendPurchaseNotification
{
    public function handle(PurchaseOrderReceived $event): void
    {
        // Dispatch job notifikasi ke queue setelah transaksi PO selesai (PRD 6.5 & 9.2)
        SendPurchaseOrderNotification::dispatch(
            $event->purchaseOrder->id,
            $event->purchaseOrder->po_number
        );
    }
}

