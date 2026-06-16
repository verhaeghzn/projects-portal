<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->text('search_summary')->nullable()->after('external_url');
            $table->timestamp('search_summary_generated_at')->nullable()->after('search_summary');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['search_summary', 'search_summary_generated_at']);
        });
    }
};
