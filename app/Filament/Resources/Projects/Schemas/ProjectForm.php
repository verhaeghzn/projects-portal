<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label('Title of the project')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                TextInput::make('slug')
                    ->required()
                    ->visible(fn ($record) => $record?->id)
                    ->maxLength(255)
                    ->unique(Project::class, 'slug', ignoreRecord: true)
                    ->disabled()
                    ->dehydrated(),

                CheckboxList::make('types')
                    ->relationship('types', 'name')
                    ->required(),

                Select::make('project_owner_id')
                    ->label('Project Owner')
                    ->relationship(
                        'owner',
                        'name',
                        fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'Staff member - supervisor'))
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->default(fn () => Auth::user()?->group?->group_leader_id ?? Auth::id())
                    ->required()
                    ->searchable(),

                Select::make('created_by_id')
                    ->label('Created By')
                    ->relationship('creator', 'name')
                    ->default(fn () => Auth::id())
                    ->disabled()
                    ->dehydrated(),

                Select::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->helperText('The organization the project is associated with. Default is TU/e.')
                    ->default(fn () => Organization::where('name', 'TU/e')->first()?->id)
                    ->required()
                    ->searchable()
                    ->preload(),

                FileUpload::make('featured_image')
                    ->label('Featured Image')
                    ->columnSpanFull()
                    ->image()
                    ->directory('projects')
                    ->disk('public')
                    ->helperText('The featured image of the project. The recommended size is 592 x 192 pixels.')
                    ->imageEditor()
                    ->rules([
                        function ($attribute, $value, $fail) {
                            // Skip validation if value is null or empty
                            if (empty($value)) {
                                return;
                            }

                            // If the file doesn't exist (e.g., cleaned up due to other validation errors),
                            // skip size validation to avoid UnableToRetrieveMetadata errors
                            if ($value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                try {
                                    // Check if file exists before trying to get size
                                    if (! $value->exists()) {
                                        return; // File was cleaned up, skip validation
                                    }

                                    // Get file size in KB (maxSize is in KB)
                                    $fileSize = $value->getSize() / 1024; // Convert bytes to KB
                                    $maxSize = 5120; // 5MB in KB

                                    if ($fileSize > $maxSize) {
                                        $fail("The {$attribute} must not be larger than ".($maxSize / 1024).' MB.');
                                    }
                                } catch (\League\Flysystem\UnableToRetrieveMetadata $e) {
                                    // File metadata cannot be retrieved (file likely deleted)
                                    // Skip validation to avoid error - this happens when file is cleaned up
                                    // due to other validation failures
                                    return;
                                } catch (\Exception $e) {
                                    // Catch any other exceptions to prevent validation errors
                                    return;
                                }
                            }
                        },
                    ]),

                Textarea::make('short_description')
                    ->label('Short Description')
                    ->helperText('One or two sentences about the project. This will be displayed on the project list page.')
                    ->required()
                    ->rows(3)
                    ->maxLength(500),

                RichEditor::make('richtext_content')
                    ->label('Content')
                    ->helperText('You can also copy paste additional images into this editor.')
                    ->required()
                    ->toolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
                        'h2',
                        'h3',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                    ])
                    ->columnSpanFull(),

                Select::make('tags')
                    ->relationship(
                        'tags',
                        'name',
                        modifyQueryUsing: function ($query) {
                            $groupId = Auth::user()?->group_id;
                            if ($groupId) {
                                $query->withCount(['projects as group_usage_count' => function ($q) use ($groupId) {
                                    $q->whereHas('supervisorLinks', function ($q2) use ($groupId) {
                                        $q2->where('supervisor_type', User::class)
                                            ->whereHas('supervisor', fn ($q3) => $q3->where('group_id', $groupId));
                                    });
                                }])
                                    ->orderByDesc('group_usage_count')
                                    ->orderBy('name');
                            } else {
                                $query->orderBy('name');
                            }
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->name.' ('.$record?->category?->value.')')
                    ->helperText("Do you really miss a tag? Please let us know, we'll add it as soon as possible.")
                    ->columnSpanFull()
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Checkbox::make('is_published')
                    ->label('Published')
                    ->helperText('When checked, the project is visible on the public project pages. Uncheck to keep it as a draft (concept).')
                    ->default(true)
                    ->live(),

                Section::make('Student Information')
                    ->description('If the project is taken, fill in the student information.')
                    ->visible(fn ($record) => $record?->id)
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([

                        TextInput::make('student_name')
                            ->label('Student Name')
                            ->visible(fn ($record) => $record?->id)
                            ->maxLength(255),

                        TextInput::make('student_email')
                            ->label('Student Email')
                            ->email()
                            ->visible(fn ($record) => $record?->id)
                            ->maxLength(255),
                    ]),

                Section::make('Supervisors')
                    ->description('Assign supervisors to the project.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('supervisorLinks')
                            ->label('Supervisors')
                            ->hiddenLabel()
                            ->relationship('supervisorLinks')
                            ->orderColumn('order_rank')
                            ->columns(2)
                            ->columnSpanFull()
                            ->reorderable()
                            ->minItems(1)
                            ->rules([
                                function (): \Closure {
                                    return function (string $attribute, $value, $fail): void {
                                        // $value is the repeater state: array of items
                                        if (! is_array($value) || count($value) === 0) {
                                            return; // let minItems(1) handle empties
                                        }

                                        $supervisorLinks = collect($value);
                                        $first = $supervisorLinks->first() ?? null;

                                        if (! is_array($first)) {
                                            return;
                                        }

                                        $type = $first['supervisor_type_selector'] ?? null;

                                        // Only enforce role for internal first supervisor
                                        if ($type !== 'internal') {
                                            $fail('The first supervisor must be an internal TU/e supervisor.');

                                            return;
                                        }

                                        $supervisorId = $first['supervisor_id'] ?? null;
                                        if (! $supervisorId) {
                                            $fail('The first supervisor must be an internal TU/e supervisor.');

                                            return;
                                        }

                                        $user = \App\Models\User::find($supervisorId);
                                        if (! $user || ! $user->hasRole('Staff member - supervisor')) {
                                            $fail('The first supervisor must be a TU/e staff member.');

                                            return;
                                        }

                                        // Check for duplicate supervisors within the form
                                        $seenInternal = [];
                                        $seenExternal = [];

                                        foreach ($supervisorLinks as $index => $link) {
                                            if (! is_array($link)) {
                                                continue;
                                            }

                                            $selector = $link['supervisor_type_selector'] ?? null;

                                            if ($selector === 'internal') {
                                                $linkSupervisorId = $link['supervisor_id'] ?? null;
                                                if ($linkSupervisorId) {
                                                    $key = User::class.'-'.$linkSupervisorId;
                                                    if (isset($seenInternal[$key])) {
                                                        $fail('Duplicate supervisor detected. The same TU/e supervisor cannot be added multiple times.');

                                                        return;
                                                    }
                                                    $seenInternal[$key] = true;
                                                }
                                            } elseif ($selector === 'external') {
                                                $externalName = $link['external_supervisor_name'] ?? null;
                                                if ($externalName) {
                                                    $key = trim(strtolower($externalName));
                                                    if (isset($seenExternal[$key])) {
                                                        $fail('Duplicate supervisor detected. The same external supervisor cannot be added multiple times.');

                                                        return;
                                                    }
                                                    $seenExternal[$key] = true;
                                                }
                                            }
                                        }
                                    };
                                },
                            ])
                            ->default(fn ($record) => $record?->id ? [] : [
                                [
                                    'supervisor_type' => User::class,
                                    'supervisor_id' => Auth::user()->group->group_leader_id,
                                ],
                                [
                                    'supervisor_type' => User::class,
                                    'supervisor_id' => Auth::id(),
                                ],
                            ])
                            ->schema([
                                Radio::make('supervisor_type_selector')
                                    ->label('Supervisor Type')
                                    ->options([
                                        'internal' => 'TU/e Supervisor',
                                        'external' => 'External Supervisor',
                                    ])
                                    ->default('internal')
                                    ->live()
                                    ->required()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record && $record->exists) {
                                            $component->state($record->isExternal() ? 'external' : 'internal');
                                        } elseif (! $state) {
                                            $component->state('internal');
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state === 'internal') {
                                            $set('external_supervisor_name', null);
                                            // If supervisor_id is not set and we're on internal, set default
                                            if (! $get('supervisor_id')) {
                                                $set('supervisor_id', Auth::id());
                                            }
                                        } else {
                                            $set('supervisor_id', null);
                                        }
                                    }),

                                Select::make('supervisor_id')
                                    ->label('TU/e Supervisor')
                                    ->options(fn () => User::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                    )
                                    ->visible(fn ($get) => $get('supervisor_type_selector') === 'internal')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn ($get) => $get('supervisor_type_selector') === 'internal')
                                    ->default(fn ($record) => $record?->supervisor_id ?? Auth::id())
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        // If no state and no record, set to authenticated user
                                        if (! $state && ! $record) {
                                            $component->state(Auth::id());
                                        }
                                    }),

                                TextInput::make('external_supervisor_name')
                                    ->label('External Supervisor Name')
                                    ->visible(fn ($get) => $get('supervisor_type_selector') === 'external')
                                    ->required(fn ($get) => $get('supervisor_type_selector') === 'external')
                                    ->maxLength(255)
                                    ->default(fn ($record) => $record?->external_supervisor_name),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                // Check if supervisor_id is present (internal) or external_supervisor_name (external)
                                $isInternal = isset($data['supervisor_id']) && ! empty($data['supervisor_id']);

                                // If supervisor_type_selector indicates internal but supervisor_id is missing, set it
                                if (isset($data['supervisor_type_selector']) && $data['supervisor_type_selector'] === 'internal' && ! $isInternal) {
                                    $data['supervisor_id'] = Auth::id();
                                    $isInternal = true;
                                }

                                if ($isInternal) {
                                    $data['supervisor_type'] = User::class;
                                    $data['external_supervisor_name'] = null;
                                } else {
                                    $data['supervisor_type'] = null;
                                    $data['supervisor_id'] = null;
                                }

                                unset($data['supervisor_type_selector']);

                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                // Check if supervisor_id is present (internal) or external_supervisor_name (external)
                                $isInternal = isset($data['supervisor_id']) && ! empty($data['supervisor_id']);

                                if ($isInternal) {
                                    $data['supervisor_type'] = User::class;
                                    $data['external_supervisor_name'] = null;
                                } else {
                                    $data['supervisor_type'] = null;
                                    $data['supervisor_id'] = null;
                                }

                                unset($data['supervisor_type_selector']);

                                return $data;
                            }),
                    ]),
            ]);
    }
}
