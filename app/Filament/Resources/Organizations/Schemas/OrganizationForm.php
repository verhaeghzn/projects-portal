<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Models\Organization;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->directory('organizations')
                    ->disk('public')
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

                TextInput::make('url')
                    ->label('URL')
                    ->url()
                    ->maxLength(255),
            ]);
    }
}
