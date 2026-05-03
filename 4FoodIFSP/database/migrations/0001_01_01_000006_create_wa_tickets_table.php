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
        Schema::create('wa_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone_number')->index();
            $table->string('customer_name')->nullable();
            $table->enum('status', ['triage', 'in_progress', 'closed'])
                ->default('triage')
                ->index();
            $table->string('agent_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('agent_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_tickets');
    }
};
