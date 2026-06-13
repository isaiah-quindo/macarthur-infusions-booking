<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index('display_order');
        });

        // Backfill: register every existing distinct service.category so the
        // categories list starts in parity with current data. Preserves the
        // first-seen display order from the services table.
        $categories = DB::table('services')
            ->select('category')
            ->whereNotNull('category')
            ->orderBy('display_order')
            ->pluck('category')
            ->unique()
            ->values();

        foreach ($categories as $i => $name) {
            DB::table('service_categories')->insert([
                'name' => $name,
                'display_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
