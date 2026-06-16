<?php

use App\Models\Group;
use App\Models\Section;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Sections
        $momSection = Section::firstOrCreate(['name' => 'Mechanics of Materials']);
        $ppSection = Section::firstOrCreate(['name' => 'Processing and Performance']);
        $msSection = Section::firstOrCreate(['name' => 'Microsystems']);

        // Groups are created before `abbrev_id` exists; skip model `saving` hooks that touch it.
        Group::withoutEvents(function () use ($momSection, $ppSection, $msSection) {
            Group::firstOrCreate([
                'name' => 'Group Geers',
                'section_id' => $momSection->id,
            ]);
            Group::firstOrCreate([
                'name' => 'Group Peerlings',
                'section_id' => $momSection->id,
            ]);
            Group::firstOrCreate([
                'name' => 'Group Remmers',
                'section_id' => $momSection->id,
            ]);
            Group::firstOrCreate([
                'name' => 'Group Rokos',
                'section_id' => $momSection->id,
            ]);

            Group::firstOrCreate([
                'name' => 'Group Anderson',
                'section_id' => $ppSection->id,
            ]);
            Group::firstOrCreate([
                'name' => 'Group Alicke',
                'section_id' => $ppSection->id,
            ]);

            Group::firstOrCreate([
                'name' => 'Group Den Toonder',
                'section_id' => $msSection->id,
            ]);
            Group::firstOrCreate([
                'name' => 'Group Luttge',
                'section_id' => $msSection->id,
            ]);
            Group::firstOrCreate([
                'name' => 'Group Wyss',
                'section_id' => $msSection->id,
            ]);
            Group::firstOrCreate([
                'name' => 'Group Wang',
                'section_id' => $msSection->id,
            ]);
            Group::firstOrCreate([
                'name' => 'Neuromorphic Engineering',
                'section_id' => $msSection->id,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete groups first (due to foreign key constraint)
        Group::whereIn('name', [
            'Group Geers',
            'Group Peerlings',
            'Group Remmers',
            'Group Rokos',
            'Group Anderson',
            'Group Alicke',
            'Group Den Toonder',
            'Group Luttge',
            'Group Wyss',
            'Group Wang',
            'Neuromorphic Engineering',
        ])->delete();

        // Delete sections
        Section::whereIn('name', [
            'Mechanics of Materials',
            'Processing and Performance',
            'Microsystems',
        ])->delete();
    }
};
