<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Stamped when the day-before reminder email goes out so the
            // scheduled command stays idempotent across re-runs/deploys.
            $table->timestampTz('reminder_sent_at')->nullable()->after('hold_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
