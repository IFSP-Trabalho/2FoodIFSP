<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('color', 7)->default('#5E6B7A')->after('slug');
        });

        $colors = [
            'admin' => '#993C1D',
            'kitchen' => '#E67E22',
            'finance' => '#2B6CB0',
            'waiter' => '#38A169',
        ];

        foreach ($colors as $slug => $color) {
            DB::table('departments')
                ->where('slug', $slug)
                ->update(['color' => $color]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
