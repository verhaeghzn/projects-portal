<?php

namespace App\Filament\Widgets;

use App\Models\Division;
use Filament\Widgets\Widget;

class ExportMyDivisionWidget extends Widget
{
    // Place it right after the AccountWidget (sort -3) so it sits beside the
    // welcome/sign-out widget in the first dashboard row.
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.export-my-division';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && $user->can('export division projects')
            && $user->group?->section?->division !== null;
    }

    public function getDivision(): ?Division
    {
        return auth()->user()?->group?->section?->division;
    }
}
