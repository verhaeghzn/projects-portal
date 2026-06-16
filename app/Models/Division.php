<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Division extends Model
{
    use Cachable, HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Abbreviation derived from name, e.g. "Thermo-Fluids Engineering (TFE)" → "TFE".
     */
    public function getAbbrevAttribute(): ?string
    {
        return preg_match('/\s*\(([A-Za-z]+)\)\s*$/', $this->name, $m) ? $m[1] : null;
    }

    /**
     * Example smart-search prompts shown on the division project index.
     *
     * @return list<string>
     */
    public static function searchSuggestionsFor(?string $slug): array
    {
        $topicSuggestions = match ($slug) {
            'thermo-fluids-engineering' => [
                'Hydrogen experiments',
                'CFD for multiphase flows',
            ],
            'dynamical-systems-design' => [
                'Reinforcement learning for vehicle control',
            ],
            'computational-experimental-mechanics' => [
                'Experiments with steel',
                'Master thesis on simulation',
            ],
            default => [
                'Experiments with steel',
                'Master thesis on simulation',
            ],
        };

        $leaderSuggestion = static::randomGroupLeaderSuggestion($slug);

        if ($leaderSuggestion === null) {
            return $topicSuggestions;
        }

        if (count($topicSuggestions) >= 2) {
            return [
                $topicSuggestions[0],
                $leaderSuggestion,
                ...array_slice($topicSuggestions, 1),
            ];
        }

        return [
            $topicSuggestions[0],
            $leaderSuggestion,
        ];
    }

    private static function randomGroupLeaderSuggestion(?string $slug): ?string
    {
        $leaderIds = Group::query()
            ->whereNotNull('group_leader_id')
            ->when($slug !== null, fn ($query) => $query->whereHas(
                'section.division',
                fn ($divisionQuery) => $divisionQuery->where('slug', $slug),
            ))
            ->distinct()
            ->pluck('group_leader_id');

        if ($leaderIds->isEmpty()) {
            return null;
        }

        $leaderName = User::query()
            ->whereIn('id', $leaderIds)
            ->inRandomOrder()
            ->value('name');

        return $leaderName ? "Supervised by {$leaderName}" : null;
    }
}
