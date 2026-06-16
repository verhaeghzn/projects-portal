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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->upSqlite();

            return;
        }

        Schema::table('project_supervisor', function (Blueprint $table) {
            // Add polymorphic columns
            $table->string('supervisor_type')->nullable()->after('project_id');
            $table->unsignedBigInteger('supervisor_id')->nullable()->after('supervisor_type');
        });

        // Migrate existing data: set supervisor_type to 'App\Models\User' and supervisor_id to user_id
        DB::table('project_supervisor')->update([
            'supervisor_type' => 'App\Models\User',
            'supervisor_id' => DB::raw('user_id'),
        ]);

        // Drop foreign key on user_id if it exists (from previous migration run)
        $userForeignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'project_supervisor' 
            AND COLUMN_NAME = 'user_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($userForeignKeys as $foreignKey) {
            DB::statement("ALTER TABLE `project_supervisor` DROP FOREIGN KEY `{$foreignKey->CONSTRAINT_NAME}`");
        }

        // Temporarily drop and recreate the project_id foreign key to free up the unique index
        // MySQL uses the unique index for the foreign key, so we need to give it its own index
        $projectForeignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'project_supervisor' 
            AND COLUMN_NAME = 'project_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        $projectFkName = null;
        foreach ($projectForeignKeys as $foreignKey) {
            $projectFkName = $foreignKey->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE `project_supervisor` DROP FOREIGN KEY `{$projectFkName}`");
        }

        Schema::table('project_supervisor', function (Blueprint $table) {
            // Make the new columns required
            $table->string('supervisor_type')->nullable(false)->change();
            $table->unsignedBigInteger('supervisor_id')->nullable(false)->change();
        });

        // Now we can drop the unique constraint
        DB::statement('ALTER TABLE `project_supervisor` DROP INDEX `project_supervisor_project_id_user_id_unique`');

        // Recreate the foreign key on project_id (it will create its own index)
        if ($projectFkName) {
            Schema::table('project_supervisor', function (Blueprint $table) {
                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            });
        }

        Schema::table('project_supervisor', function (Blueprint $table) {
            // Drop old user_id column
            $table->dropColumn('user_id');

            // Add new unique constraint
            $table->unique(['project_id', 'supervisor_type', 'supervisor_id'], 'project_supervisor_unique');
        });
    }

    /**
     * SQLite cannot use the MySQL information_schema FK introspection above; rebuild the table.
     */
    private function upSqlite(): void
    {
        Schema::dropIfExists('project_supervisor');

        Schema::create('project_supervisor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('supervisor_type');
            $table->unsignedBigInteger('supervisor_id');
            $table->integer('order_rank');
            $table->timestamps();

            $table->unique(['project_id', 'supervisor_type', 'supervisor_id'], 'project_supervisor_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrate data back: only for User type supervisors
        $userSupervisors = DB::table('project_supervisor')
            ->where('supervisor_type', 'App\Models\User')
            ->get();

        Schema::table('project_supervisor', function (Blueprint $table) {
            // Add back user_id column
            $table->unsignedBigInteger('user_id')->nullable()->after('project_id');
        });

        // Update user_id for User type supervisors
        foreach ($userSupervisors as $supervisor) {
            DB::table('project_supervisor')
                ->where('id', $supervisor->id)
                ->update(['user_id' => $supervisor->supervisor_id]);
        }

        Schema::table('project_supervisor', function (Blueprint $table) {
            // Make user_id required
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            // Drop polymorphic columns
            $table->dropUnique('project_supervisor_unique');
            $table->dropColumn(['supervisor_type', 'supervisor_id']);

            // Restore old unique constraint
            $table->unique(['project_id', 'user_id']);
        });
    }
};
