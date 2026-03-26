<?php

use App\Models\Section;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $usedAbbreviations = Section::whereNotNull('abbrev_id')
            ->where('abbrev_id', '!=', '')
            ->pluck('abbrev_id')
            ->map(fn (string $abbrev): string => strtoupper($abbrev))
            ->all();

        Section::where(function ($query): void {
            $query->whereNull('abbrev_id')
                ->orWhere('abbrev_id', '');
        })->orderBy('id')->each(function (Section $section) use (&$usedAbbreviations): void {
            $baseAbbrev = collect(preg_split('/\s+/', trim($section->name)) ?: [])
                ->filter()
                ->map(function (string $word): string {
                    $cleaned = preg_replace('/[^A-Za-z0-9]/', '', $word) ?? '';

                    return $cleaned !== '' ? strtoupper($cleaned[0]) : '';
                })
                ->implode('');

            if ($baseAbbrev === '') {
                $baseAbbrev = strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $section->name) ?? 'SEC', 0, 3));
            }

            $baseAbbrev = Str::substr($baseAbbrev, 0, 4);
            if ($baseAbbrev === '') {
                $baseAbbrev = 'SEC';
            }

            $candidate = $baseAbbrev;
            $counter = 1;
            while (in_array($candidate, $usedAbbreviations, true)) {
                $suffix = (string) $counter;
                $candidate = Str::substr($baseAbbrev, 0, max(1, 4 - strlen($suffix))).$suffix;
                $counter++;
            }

            $section->update(['abbrev_id' => $candidate]);
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
