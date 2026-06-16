@php
    $division = $this->getDivision();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex h-full flex-col justify-center gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Download an Excel file with all projects of
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $division?->name }}</span>
                (numbers, titles, sections and supervisors) to divide them.
            </p>

            <x-filament::button
                tag="a"
                :href="route('admin.divisions.projects.export', $division)"
                icon="heroicon-o-arrow-down-tray"
                color="primary"
                class="w-full sm:w-auto"
            >
                Export my division (Excel)
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
