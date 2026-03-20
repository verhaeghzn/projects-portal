<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('random_ranking')->nullable()->after('publication_status');
            $table->index('random_ranking');
        });

        // Initialize rankings for existing published, available projects
        $projectIds = DB::table('projects')
            ->where('publication_status', 'published')
            ->whereNull('student_name')
            ->whereNull('student_email')
            ->pluck('id')
            ->shuffle()
            ->values();

        // Assign rankings starting from 1
        foreach ($projectIds as $index => $projectId) {
            DB::table('projects')
                ->where('id', $projectId)
                ->update(['random_ranking' => $index + 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['random_ranking']);
            $table->dropColumn('random_ranking');
        });
    }
};
