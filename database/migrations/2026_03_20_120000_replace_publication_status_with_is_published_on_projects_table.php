<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('organization_id');
        });

        DB::table('projects')->where('publication_status', 'concept')->update(['is_published' => false]);

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('publication_status');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('publication_status')->default('published')->after('organization_id');
        });

        DB::table('projects')->where('is_published', false)->update(['publication_status' => 'concept']);
        DB::table('projects')->where('is_published', true)->update(['publication_status' => 'published']);

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
