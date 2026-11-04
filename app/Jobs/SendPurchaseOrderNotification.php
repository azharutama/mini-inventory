<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPurchaseOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $purchaseOrderId,
        public readonly string $poNumber,
    ) {}

    public function handle(): void
    {
        // Catat log notifikasi penerimaan PO secara asynchronous (PRD 6.4)
        Log::info("Purchase Order {$this->poNumber} (ID: {$this->purchaseOrderId}) berhasil diterima.");
    }
}

