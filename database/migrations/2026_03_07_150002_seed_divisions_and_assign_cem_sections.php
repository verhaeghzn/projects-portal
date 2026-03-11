<?php

use App\Models\Division;
use App\Models\Section;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tfe = Division::firstOrCreate(
            ['slug' => 'thermo-fluids-engineering'],
            ['name' => 'Thermo-Fluids Engineering (TFE)']
        );
        $cem = Division::firstOrCreate(
            ['slug' => 'computational-experimental-mechanics'],
            ['name' => 'Computational and Experimental Mechanics (CEM)']
        );
        $dsd = Division::firstOrCreate(
            ['slug' => 'dynamical-systems-design'],
            ['name' => 'Dynamical Systems Design (DSD)']
        );

        // Assign existing CEM sections (Mechanics of Materials, Processing and Performance, Microsystems) to CEM division
        $cemSectionNames = ['Mechanics of Materials', 'Processing and Performance', 'Microsystems'];
        Section::whereIn('name', $cemSectionNames)->update(['division_id' => $cem->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Section::whereNotNull('division_id')->update(['division_id' => null]);
        Division::query()->delete();
    }
};
