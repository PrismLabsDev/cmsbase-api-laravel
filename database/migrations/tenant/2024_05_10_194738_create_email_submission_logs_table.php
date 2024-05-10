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
        Schema::create('email_submission_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_submission_id')->references('id')->on('email_submissions')->onDelete('cascade');
            $table->json('submission_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_submission_logs');
    }
};
