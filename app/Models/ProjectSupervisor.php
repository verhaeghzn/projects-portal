<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;

class ProjectSupervisor extends Model
{
    protected $table = 'project_supervisor';

    protected $fillable = [
        'project_id',
        'supervisor_type',
        'supervisor_id',
        'external_supervisor_name',
        'order_rank',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($projectSupervisor) {
            // Check for duplicate supervisors before creating
            if ($projectSupervisor->project_id) {
                $query = static::where('project_id', $projectSupervisor->project_id);
                
                if ($projectSupervisor->isExternal()) {
                    // Check for duplicate external supervisor
                    $exists = $query->whereNull('supervisor_type')
                        ->whereNull('supervisor_id')
                        ->where('external_supervisor_name', $projectSupervisor->external_supervisor_name)
                        ->exists();
                    
                    if ($exists) {
                        Notification::make()
                            ->title('Error saving supervisor')
                            ->body('This external supervisor is already assigned to this project.')
                            ->danger()
                            ->send();
                        throw ValidationException::withMessages([
                            'supervisorLinks' => 'This external supervisor is already assigned to this project.',
                        ]);
                    }
                } else {
                    $internalSupervisor = User::find($projectSupervisor->supervisor_id);
                    if ($internalSupervisor?->hasRole('Support colleague')) {
                        Notification::make()
                            ->title('Error saving supervisor')
                            ->body('Support colleagues cannot be assigned as supervisors.')
                            ->danger()
                            ->send();
                        throw ValidationException::withMessages([
                            'supervisorLinks' => 'Support colleagues cannot be assigned as supervisors.',
                        ]);
                    }

                    // Check for duplicate internal supervisor
                    $exists = $query->where('supervisor_type', $projectSupervisor->supervisor_type)
                        ->where('supervisor_id', $projectSupervisor->supervisor_id)
                        ->exists();
                    
                    if ($exists) {
                        Notification::make()
                            ->title('Error saving supervisor')
                            ->body('This supervisor is already assigned to this project.')
                            ->danger()
                            ->send();
                        throw ValidationException::withMessages([
                            'supervisorLinks' => 'This supervisor is already assigned to this project.',
                        ]);
                    }
                }
            }
        });

        static::saved(function ($projectSupervisor) {
            // Validate that the first supervisor is an internal staff supervisor
            // This works for both creating and updating projects
            // We validate whenever any supervisor is saved to catch order changes
            if ($projectSupervisor->project) {
                $projectSupervisor->project->loadMissing('supervisorLinks.supervisor.roles');

                $firstSupervisorLink = $projectSupervisor->project->supervisorLinks
                    ->sortBy('order_rank')
                    ->first();

                if ($firstSupervisorLink) {
                    $isValid = false;

                    if (!$firstSupervisorLink->isExternal()) {
                        $supervisor = $firstSupervisorLink->supervisor;

                        if ($supervisor instanceof User && $supervisor->hasRole('Staff member - supervisor')) {
                            $isValid = true;
                        }
                    }

                    if (! $isValid) {
                        // Also show a filament notification
                        Notification::make()
                            ->title('Error saving this project')
                            ->body('The first supervisor must be a TU/e staff member.')
                            ->danger()
                            ->send();
                        throw ValidationException::withMessages([
                            'supervisorLinks' => 'The first supervisor must be a TU/e staff member.',
                        ]);
                    }
                }

                // Generate project number if project doesn't have one yet
                if (empty($projectSupervisor->project->project_number)) {
                    $projectSupervisor->project->generateProjectNumber();
                }
            }
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function supervisor(): MorphTo
    {
        // Uses supervisor_type & supervisor_id automatically
        // Returns null for external supervisors (where supervisor_type is null)
        return $this->morphTo();
    }
    
    /**
     * Check if this is an external supervisor
     */
    public function isExternal(): bool
    {
        return empty($this->supervisor_type) && !empty($this->external_supervisor_name);
    }
    
    /**
     * Get the supervisor name (either from the model or external name)
     */
    public function getNameAttribute(): string
    {
        if ($this->isExternal()) {
            return $this->external_supervisor_name;
        }
        
        return $this->supervisor?->name ?? '';
    }
}