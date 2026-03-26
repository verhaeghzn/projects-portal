<?php

use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $usedAbbreviations = Group::whereNotNull('abbrev_id')
            ->where('abbrev_id', '!=', '')
            ->pluck('abbrev_id')
            ->map(fn (string $abbrev): string => strtoupper($abbrev))
            ->all();

        Group::where(function ($query): void {
            $query->whereNull('abbrev_id')
                ->orWhere('abbrev_id', '');
        })->orderBy('id')->each(function (Group $group) use (&$usedAbbreviations): void {
            $nameWithoutPrefix = preg_replace('/^Group\s+/i', '', $group->name) ?? $group->name;
            $cleaned = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $nameWithoutPrefix) ?? '');

            $base = Str::substr($cleaned, 0, 4);
            if ($base === '') {
                $base = 'GRP';
            }

            $candidate = $base;
            $counter = 1;
            while (in_array($candidate, $usedAbbreviations, true)) {
                $suffix = (string) $counter;
                $candidate = Str::substr($base, 0, max(1, 4 - strlen($suffix))).$suffix;
                $counter++;
            }

            $group->update(['abbrev_id' => $candidate]);
            $usedAbbreviations[] = $candidate;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty: we cannot safely determine which abbrev_id values were auto-generated.
    }
};
