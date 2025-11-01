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
        // Create a pivot table to link meetings and regs. One meeting can have many regs,
        // and a reg can (optionally) register for multiple meetings in the future.
        Schema::create('meeting_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->unsignedBigInteger('reg_id');
            $table->timestamps();

            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
            $table->foreign('reg_id')->references('id')->on('regs')->onDelete('cascade');

            $table->unique(['meeting_id', 'reg_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_registrations');
    }
};
