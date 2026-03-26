<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory, Cachable;

    protected $fillable = [
        'name',
        'section_id',
        'abbrev_id',
        'external_url',
        'group_leader_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (Group $group): void {
            if (trim((string) $group->abbrev_id) !== '') {
                return;
            }

            $group->abbrev_id = static::generateUniqueAbbrevId($group->name, $group->id);
        });
    }

    private static function generateUniqueAbbrevId(string $groupName, ?int $ignoreGroupId = null): string
    {
        $nameWithoutPrefix = preg_replace('/^Group\s+/i', '', $groupName) ?? $groupName;
        $cleaned = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $nameWithoutPrefix) ?? '');

        $base = Str::substr($cleaned, 0, 4);
        if ($base === '') {
            $base = 'GRP';
        }

        $candidate = $base;
        $counter = 1;

        while (static::query()
            ->where('abbrev_id', $candidate)
            ->when($ignoreGroupId, fn ($query) => $query->where('id', '!=', $ignoreGroupId))
            ->exists()) {
            $suffix = (string) $counter;
            $candidate = Str::substr($base, 0, max(1, 4 - strlen($suffix))).$suffix;
            $counter++;
        }

        return $candidate;
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'group_leader_id');
    }
}
