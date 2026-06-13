<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_blocks', function (Blueprint $table) {
            $table->id();
            // 0 = Sunday … 6 = Saturday, matching Carbon::dayOfWeek and availability_rules.
            $table->unsignedTinyInteger('day_of_week');
            // Clinic-local wall-clock times ("12:00", "13:00").
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('day_of_week');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_blocks');
    }
};
