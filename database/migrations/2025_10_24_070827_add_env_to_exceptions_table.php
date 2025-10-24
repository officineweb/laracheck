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
            $table->string('env')->nullable()->after('host');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exceptions', function (Blueprint $table) {
            $table->dropColumn('env');
        });
    }
};
