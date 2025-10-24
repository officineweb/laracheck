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
        Schema::table('exceptions', function (Blueprint $table) {
            $table->integer('code')->nullable()->after('method');
            $table->timestamp('notified_at')->nullable()->after('mailed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exceptions', function (Blueprint $table) {
            $table->dropColumn(['code', 'notified_at']);
        });
    }
};

