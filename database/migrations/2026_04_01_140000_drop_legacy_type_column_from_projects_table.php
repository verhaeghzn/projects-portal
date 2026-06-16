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
        if (! Schema::hasColumn('projects', 'type')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('projects', 'type')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('type', ['internship', 'bachelor_thesis', 'master_thesis'])->default('master_thesis');
        });
    }
};
