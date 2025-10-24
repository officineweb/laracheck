<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('projects', 'sites');
        Schema::rename('project_user', 'site_user');

        // Rinomina colonna project_id in site_id nelle tabelle correlate
        Schema::table('exceptions', function (Blueprint $table) {
            $table->renameColumn('project_id', 'site_id');
        });

        Schema::table('site_user', function (Blueprint $table) {
            $table->renameColumn('project_id', 'site_id');
        });
    }

    public function down(): void
    {
        Schema::table('exceptions', function (Blueprint $table) {
            $table->renameColumn('site_id', 'project_id');
        });

        Schema::table('site_user', function (Blueprint $table) {
            $table->renameColumn('site_id', 'project_id');
        });

        Schema::rename('site_user', 'project_user');
        Schema::rename('sites', 'projects');
    }
};
