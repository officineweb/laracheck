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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->boolean('receive_email')->default(true);
            $table->string('slack_webhook')->nullable();
            $table->string('discord_webhook')->nullable();
            $table->string('custom_webhook')->nullable();
            $table->timestamp('last_exception_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
