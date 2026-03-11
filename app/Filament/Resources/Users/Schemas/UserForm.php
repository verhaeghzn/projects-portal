<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Group;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                FileUpload::make('avatar_url')
                    ->label('Avatar')
                    ->image()
                    ->directory('avatars')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        null,
                        '1:1',
                    ])
                    ->imageResizeMode('cover')
                    ->imageResizeTargetHeight(100)
                    ->imageResizeTargetWidth(100)
                    ->avatar()
                    ->columnSpanFull()
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
                                    if (!$value->exists()) {
                                        return; // File was cleaned up, skip validation
                                    }
                                    
                                    // Get file size in KB (maxSize is in KB)
                                    $fileSize = $value->getSize() / 1024; // Convert bytes to KB
                                    $maxSize = 2048; // 2MB in KB
                                    
                                    if ($fileSize > $maxSize) {
                                        $fail("The {$attribute} must not be larger than " . ($maxSize / 1024) . " MB.");
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

                Select::make('group_id')
                    ->label('Group')
                    ->preload()
                    ->relationship('group', 'name')
                    ->searchable(),

                TextInput::make('password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof \App\Filament\Resources\Users\Pages\CreateUser)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                    ->minLength(8),

                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->searchable(),
            ]);
    }
}
