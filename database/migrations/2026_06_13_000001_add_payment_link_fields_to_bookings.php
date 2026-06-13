<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_link_id')->nullable()->after('hold_expires_at');
            $table->string('payment_link_order_id')->nullable()->after('payment_link_id');
            $table->string('payment_link_url', 512)->nullable()->after('payment_link_order_id');
            $table->index('payment_link_order_id');
        });

        // Dedupes concurrent return-URL + webhook completion for the same
        // Square payment so we can't create two Payment rows for one charge.
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['square_payment_id']);
            $table->unique('square_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['square_payment_id']);
            $table->index('square_payment_id');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['payment_link_order_id']);
            $table->dropColumn(['payment_link_id', 'payment_link_order_id', 'payment_link_url']);
        });
    }
};
