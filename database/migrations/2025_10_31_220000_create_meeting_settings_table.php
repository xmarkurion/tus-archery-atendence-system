<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meeting_settings', function (Blueprint $table) {
            $table->id();
            // store selected days as JSON array of day names (Mon..Sun)
            $table->json('selected_days')->nullable();
            // whether automatic creation is enabled
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        // seed default settings with Friday selected
        DB::table('meeting_settings')->insert([
            'selected_days' => json_encode(['Friday']),
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_settings');
    }
};

