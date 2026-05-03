<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('table_id')
                ->nullable()
                ->constrained('tables')
                ->restrictOnDelete();
            $table->enum('origin', ['table', 'delivery'])->default('table')->index();
            $table->enum('status', ['pending', 'in_progress', 'ready', 'cancelled'])
                ->default('pending')
                ->index();
            $table->boolean('paid')->default(false)->index();
            $table->foreignUuid('wa_ticket_id')
                ->nullable()
                ->constrained('wa_tickets')
                ->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('delivery_address')->nullable();
            $table->timestamps();

            $table->index(['table_id', 'paid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
