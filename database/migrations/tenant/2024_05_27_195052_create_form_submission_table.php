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
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('origin')->default('localhost');
            $table->boolean('log')->default(true);
            $table->boolean('send_mail')->default(false);
            $table->boolean('recaptcha')->default(false);
            $table->boolean('turnstile')->default(false);
            $table->string('recaptcha_secret')->nullable();
            $table->string('turnstile_secret')->nullable();
            $table->timestamps();

            $table->index(['name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};