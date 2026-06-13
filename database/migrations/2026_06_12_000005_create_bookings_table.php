<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('status')->default('pending_payment');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('notes')->nullable();
            $table->timestampTz('hold_expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index('customer_email');
        });

        // The slot lock: two bookings whose [starts_at, ends_at) ranges
        // overlap cannot both hold a slot-blocking status. Races resolve
        // here, at the database, not in PHP.
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement(
            "ALTER TABLE bookings ADD CONSTRAINT bookings_no_overlap
             EXCLUDE USING gist (tstzrange(starts_at, ends_at) WITH &&)
             WHERE (status IN ('pending_payment', 'confirmed'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
