<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'email',
        'password',
        'surf_id',
        'group_id',
        'avatar_url',
        'invitation_token',
        'invitation_sent_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'ownedProjects',
        'supervisedProjects',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invitation_sent_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'project_owner_id');
    }

    public function supervisedProjects(): BelongsToMany
    {
        return $this->morphedByMany(Project::class, 'supervisor', 'project_supervisor', 'supervisor_id', 'project_id')
            ->withPivot('order_rank')
            ->orderByPivot('order_rank');
    }

    public function projects(): BelongsToMany
    {
        return $this->supervisedProjects();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['Administrator', 'Staff member - supervisor', 'Researcher', 'Support colleague']);
    }

    /**
     * Only administrators may impersonate other users.
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole('Administrator');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? \Illuminate\Support\Facades\Storage::url($this->avatar_url) : null;
    }

    /**
     * Find user by email for SAML authentication
     */
    public static function findByEmailForSaml(string $email): ?self
    {
        return static::where('email', $email)->first();
    }
}
