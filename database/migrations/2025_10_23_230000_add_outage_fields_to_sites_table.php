<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('check_url')->nullable()->after('url');
            $table->boolean('enable_uptime_check')->default(true)->after('check_url');
            $table->string('email_outage')->nullable()->after('discord_webhook');
            $table->string('email_resolved')->nullable()->after('email_outage');
            $table->datetime('checked_at')->nullable()->after('email_resolved');
            $table->boolean('is_online')->default(true)->after('checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'check_url',
                'enable_uptime_check',
                'email_outage',
                'email_resolved',
                'checked_at',
                'is_online',
            ]);
        });
    }
};

