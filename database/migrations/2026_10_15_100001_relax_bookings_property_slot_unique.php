<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * unique(property_id, scheduled_at) made a cancelled booking block re-booking of its slot
 * and turned races into uncaught QueryExceptions. Overlap is enforced in BookingService
 * (lockForUpdate), so the DB keeps only a plain index.
 *
 * The plain index is added BEFORE the unique one is dropped: on MySQL the unique index
 * is what backs the property_id foreign key and cannot be removed on its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['property_id', 'scheduled_at'], 'bookings_property_slot_index');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['property_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        // Re-adding the unique key fails if duplicate (property_id, scheduled_at) rows
        // (e.g. a re-booked cancelled slot) exist; clean those up before rolling back.
        Schema::table('bookings', function (Blueprint $table) {
            $table->unique(['property_id', 'scheduled_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_property_slot_index');
        });
    }
};
