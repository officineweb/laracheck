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
        Schema::create('exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();

            // Exception details
            $table->string('host')->nullable();
            $table->string('method')->nullable();
            $table->text('full_url')->nullable();
            $table->text('exception')->nullable();
            $table->text('error')->nullable();
            $table->string('line')->nullable();
            $table->string('file')->nullable();
            $table->string('class')->nullable();

            // Status
            $table->string('status')->default('OPEN'); // OPEN, READ, FIXED, DONE
            $table->boolean('mailed')->default(false);

            // User data
            $table->json('user')->nullable();
            $table->json('storage')->nullable();
            $table->json('executor')->nullable();
            $table->json('additional')->nullable();

            // Sharing
            $table->string('publish_hash')->unique()->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'exception', 'line']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }
};
