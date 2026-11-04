<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSalesOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $salesOrderId,
        public readonly string $orderNumber,
    ) {}

    public function handle(): void
    {
        // Catat log notifikasi pengeluaran SO secara asynchronous (PRD 6.4)
        Log::info("Sales Order {$this->orderNumber} (ID: {$this->salesOrderId}) berhasil dikeluarkan.");
    }
}

