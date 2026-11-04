<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD 5.7 & 8.1: Tabel stock_movements dengan index stock_unit_id & composite index (transaction_date, movement_type)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_unit_id')->constrained('stock_units')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->enum('movement_type', ['in', 'out']);
            $table->timestamps();

            $table->index('stock_unit_id');
            $table->index(['transaction_date', 'movement_type']);
            $table->index(['stock_unit_id', 'movement_type', 'transaction_date'], 'idx_stock_movements_unit_type_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

