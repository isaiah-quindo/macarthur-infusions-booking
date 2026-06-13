<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('name');
            $table->json('included')->nullable()->after('description');
            $table->json('benefits')->nullable()->after('included');
            $table->json('faqs')->nullable()->after('benefits');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'included', 'benefits', 'faqs']);
        });
    }
};
