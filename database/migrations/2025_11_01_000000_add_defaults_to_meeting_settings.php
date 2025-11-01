<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meeting_settings', function (Blueprint $table) {
            // default start time as time-of-day (HH:MM:SS)
            $table->time('default_start_time')->nullable()->after('enabled');
            // default duration in minutes
            $table->integer('default_duration')->default(60)->after('default_start_time');
        });

        // If there's an existing settings row, ensure sensible defaults
        $settings = DB::table('meeting_settings')->first();
        if ($settings) {
            DB::table('meeting_settings')->where('id', $settings->id)->update([
                'default_start_time' => $settings->default_start_time ?? '18:00:00',
                'default_duration' => $settings->default_duration ?? 60,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_settings', function (Blueprint $table) {
            $table->dropColumn(['default_start_time', 'default_duration']);
        });
    }
};

