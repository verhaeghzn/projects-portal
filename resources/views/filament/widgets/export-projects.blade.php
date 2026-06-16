<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex h-full flex-col justify-center gap-3">
            <div>
                <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Export projects
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Download a CSV with all project numbers, titles, divisions, sections and supervisors to divide the projects.
                </p>
            </div>

            <x-filament::button
                tag="a"
                :href="route('admin.projects.export')"
                icon="heroicon-o-arrow-down-tray"
                color="primary"
                class="w-full sm:w-auto"
            >
                Export projects (CSV)
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
