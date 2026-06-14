<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_consents', function (Blueprint $table) {
            $table->id();
            // One consent record per booking. Kept in its own table (rather
            // than columns on bookings) so the consent record survives even
            // if booking fields are later edited.
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();

            // Filename date of the published policy / collection notice the
            // patient saw at submit time (e.g. "2026-06-14"). Versions are
            // immutable on disk — never edit a published one, publish a new
            // file instead.
            $table->string('privacy_policy_version', 32);
            $table->string('collection_notice_version', 32);

            $table->timestampTz('consented_at');
            // inet covers both IPv4 and IPv6.
            $table->ipAddress('consent_ip')->nullable();
            $table->string('consent_user_agent', 500)->nullable();

            $table->timestamps();

            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_consents');
    }
};
