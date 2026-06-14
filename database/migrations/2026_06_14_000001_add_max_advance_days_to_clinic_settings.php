<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_settings', function (Blueprint $table) {
            // How far in advance (in days) patients can book.
            $table->unsignedSmallInteger('max_advance_days')->default(60)->after('concurrent_capacity');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_settings', function (Blueprint $table) {
            $table->dropColumn('max_advance_days');
        });
    }
};
