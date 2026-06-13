<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the one-patient-at-a-time exclusion constraint. Capacity
        // enforcement now lives in PHP (BookingController + advisory locks).
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_no_overlap');

        Schema::create('clinic_settings', function (Blueprint $table) {
            $table->id();
            // How many patients can be in treatment at the same time.
            $table->unsignedSmallInteger('concurrent_capacity')->default(1);
            $table->timestamps();
        });

        DB::table('clinic_settings')->insert([
            'concurrent_capacity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_settings');

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement(
            "ALTER TABLE bookings ADD CONSTRAINT bookings_no_overlap
             EXCLUDE USING gist (tstzrange(starts_at, ends_at) WITH &&)
             WHERE (status IN ('pending_payment', 'confirmed'))"
        );
    }
};
