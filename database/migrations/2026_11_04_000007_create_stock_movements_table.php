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

            $table->index(['transaction_date', 'movement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

