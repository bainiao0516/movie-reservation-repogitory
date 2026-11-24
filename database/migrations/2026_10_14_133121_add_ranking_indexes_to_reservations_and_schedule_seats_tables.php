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
        Schema::table('reservations', function (Blueprint $table) {
            $table->index(
                ['status', 'created_at'],
                'reservations_status_created_at_index'
            );
        });

        Schema::table('schedule_seats', function (Blueprint $table) {
            $table->index(
                ['schedule_id', 'status', 'reservation_id'],
                'schedule_seats_ranking_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_seats', function (Blueprint $table) {
            $table->dropIndex('schedule_seats_ranking_index');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_status_created_at_index');
        });
    }
};
