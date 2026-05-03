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
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->index();
            $table->string('department_id');
            $table->boolean('must_reset_password')->default(true);
            $table->timestamps();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
