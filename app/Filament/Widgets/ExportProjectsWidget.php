<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ExportProjectsWidget extends Widget
{
    // Place it right after the AccountWidget (sort -3) so it sits beside the
    // welcome/sign-out widget in the first dashboard row.
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.export-projects';
}
