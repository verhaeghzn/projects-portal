@extends('layouts.app')

@section('title', $selectedDivision ? 'Research Projects – ' . $selectedDivision['name'] : 'Research Projects')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
        <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-heading text-gray-900 mb-2 sm:mb-4">
                Research Projects@if ($selectedDivision) – {{ $selectedDivision['name'] }}@endif
            </h1>
            <p class="text-base sm:text-lg text-gray-600 mb-4 sm:mb-6">Explore available research opportunities @if ($selectedSupervisorName)
                    supervised by {{ $selectedSupervisorName }}
                @endif
            </p>

            {{-- Mobile filter toggle button --}}
            <button id="filter-toggle"
                class="sm:hidden w-full flex items-center justify-between px-4 py-3 mb-3 bg-gray-50 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#7fabc9] focus:ring-offset-2 transition-colors"
                onclick="toggleFilters()" aria-expanded="false" aria-controls="filter-section">
                <span>Filters</span>
                <svg id="filter-toggle-icon" class="h-5 w-5 text-gray-500 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            {{-- Filter section --}}
            <div id="filter-section"
                class="filter-section-mobile sm:flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 lg:gap-6">
                <div class="flex flex-col w-full sm:w-auto">
                    <label for="type-filter" class="text-xs font-medium text-gray-600 mb-1">Filter by type</label>
                    <select id="type-filter" onchange="updateFilters('type', this.value)"
                        class="border border-gray-300 rounded-md px-3 sm:px-4 py-2 text-sm focus:ring-[#7fabc9] focus:border-[#7fabc9] w-full">
                        <option value="">All Projects</option>
                        <option value="bachelor_thesis" {{ request('type') === 'bachelor_thesis' ? 'selected' : '' }}>
                            Bachelor Thesis Projects</option>
                        <option value="master_thesis" {{ request('type') === 'master_thesis' ? 'selected' : '' }}>Master
                            Thesis Projects</option>
                    </select>
                </div>

                <div class="flex flex-col w-full sm:w-auto">
                    <label for="nature-filter" class="text-xs font-medium text-gray-600 mb-1">Project nature</label>
                    <select id="nature-filter" onchange="updateFilters('nature', this.value)"
                        class="border border-gray-300 rounded-md px-3 sm:px-4 py-2 text-sm focus:ring-[#7fabc9] focus:border-[#7fabc9] w-full">
                        <option value="">All</option>
                        @foreach ($natureTags as $tag)
                            <option value="{{ $tag->slug }}" {{ request('nature') === $tag->slug ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col w-full sm:w-auto">
                    <label for="focus-filter" class="text-xs font-medium text-gray-600 mb-1">Focus</label>
                    <select id="focus-filter" onchange="updateFilters('focus', this.value)"
                        class="border border-gray-300 rounded-md px-3 sm:px-4 py-2 text-sm focus:ring-[#7fabc9] focus:border-[#7fabc9] w-full">
                        <option value="">All</option>
                        @foreach ($focusTags as $tag)
                            <option value="{{ $tag->slug }}" {{ request('focus') === $tag->slug ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col w-full sm:w-auto">
                    <label for="section-filter" class="text-xs font-medium text-gray-600 mb-1">Section</label>
                    <select id="section-filter" onchange="updateFilters('section', this.value)"
                        class="border border-gray-300 rounded-md px-3 sm:px-4 py-2 text-sm focus:ring-[#7fabc9] focus:border-[#7fabc9] w-full">
                        <option value="">All</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->slug }}"
                                {{ request('section') === $section->slug ? 'selected' : '' }}>{{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col w-full sm:w-auto">
                    <label for="supervisor-filter" class="text-xs font-medium text-gray-600 mb-1">Supervisor</label>
                    <select id="supervisor-filter" onchange="updateFilters('supervisor', this.value)"
                        class="border border-gray-300 rounded-md px-3 sm:px-4 py-2 text-sm focus:ring-[#7fabc9] focus:border-[#7fabc9] w-full">
                        <option value="">All</option>
                        @foreach ($supervisors as $supervisor)
                            <option value="{{ $supervisor['slug'] }}"
                                {{ request('supervisor') === $supervisor['slug'] ? 'selected' : '' }}>{{ $supervisor['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <script>
                // Toggle filter section on mobile
                function toggleFilters() {
                    const filterSection = document.getElementById('filter-section');
                    const toggleButton = document.getElementById('filter-toggle');
                    const toggleIcon = document.getElementById('filter-toggle-icon');

                    // Only toggle on mobile (screen width < 640px)
                    if (window.innerWidth < 640) {
                        if (filterSection.classList.contains('filter-visible')) {
                            filterSection.classList.remove('filter-visible');
                            toggleButton.setAttribute('aria-expanded', 'false');
                            toggleIcon.style.transform = 'rotate(0deg)';
                        } else {
                            filterSection.classList.add('filter-visible');
                            toggleButton.setAttribute('aria-expanded', 'true');
                            toggleIcon.style.transform = 'rotate(180deg)';
                        }
                    }
                }

                function updateFilters(filterName, filterValue) {
                    // Get current filter values
                    const type = document.getElementById('type-filter').value;
                    const nature = document.getElementById('nature-filter').value;
                    const section = document.getElementById('section-filter').value;
                    const focus = document.getElementById('focus-filter').value;
                    const supervisor = document.getElementById('supervisor-filter')?.value || '';

                    // Build new params with all current filter values
                    const params = new URLSearchParams();
                    if (type) params.set('type', type);
                    if (nature) params.set('nature', nature);
                    if (section) params.set('section', section);
                    if (focus) params.set('focus', focus);
                    if (supervisor) params.set('supervisor', supervisor);

                    // Navigate with all filters
                    const queryString = params.toString();
                    window.location.href = '{{ route('projects.index') }}' + (queryString ? '?' + queryString : '');
                }
            </script>
        </div>

        @if ($projects->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
                @foreach ($projects as $project)
                    <a href="{{ route('projects.show', $project) }}"
                        class="group bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300 flex flex-col h-full">

                        @if ($project->featured_image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($project->featured_image) }}"
                                alt="{{ $project->name }}" class="w-full aspect-[592/192] object-cover">
                        @else
                            <div
                                class="w-full aspect-[592/192] bg-gradient-to-br from-[#7fabc9] to-[#5a8ba8] flex items-center justify-center">
                                <span
                                    class="text-white text-xl sm:text-2xl font-bold">{{ substr($project->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <div class="p-4 sm:p-6 flex flex-col flex-grow">
                            <div class="flex items-start justify-between mb-2 gap-2">
                                <h2
                                    class="text-base sm:text-lg font-heading text-gray-900 group-hover:text-[#7fabc9] transition-colors flex-1">
                                    {{ $project->name }}</h2>
                            </div>
                            <p class="text-gray-600 text-sm mb-3 sm:mb-4 line-clamp-3">{{ $project->short_description }}
                            </p>

                            @if ($project->tags->count() > 0)
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach ($project->tags as $tag)
                                        @php
                                            $colorClasses = match ($tag->category->value) {
                                                'group' => 'bg-blue-100 text-blue-800',
                                                'nature' => 'bg-green-100 text-green-800',
                                                'focus' => 'bg-amber-100 text-amber-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $colorClasses }}">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($project->supervisorLinks->count() > 0)
                                <div class="mt-auto">
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center gap-2 sm:space-x-2 pt-3 sm:pt-4 border-t border-gray-200">
                                        <span class="text-xs sm:text-sm text-gray-500">Supervisors:</span>
                                        <div class="flex items-center gap-2 sm:gap-0">
                                            <div class="flex -space-x-2">
                                                @foreach ($project->supervisorLinks->take(3) as $index => $supervisorLink)
                                                    @php
                                                        $supervisor = $supervisorLink->supervisor;
                                                    @endphp
                                                    @if (!$supervisorLink->isExternal())
                                                        <div class="relative {{ $index === 0 ? 'z-30' : ($index === 1 ? 'z-20' : 'z-10') }} w-7 h-7 sm:w-8 sm:h-8 rounded-full border-2 border-white overflow-hidden"
                                                            title="{{ $supervisor->name }}">
                                                            @if ($supervisor->avatar_url)
                                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($supervisor->avatar_url) }}"
                                                                    alt="{{ $supervisor->name }}"
                                                                    class="w-full h-full object-cover">
                                                            @else
                                                                <div
                                                                    class="w-full h-full bg-[#7fabc9] flex items-center justify-center text-white text-xs font-medium">
                                                                    {{ substr($supervisor->name, 0, 1) }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="relative {{ $index === 0 ? 'z-30' : ($index === 1 ? 'z-20' : 'z-10') }} w-7 h-7 sm:w-8 sm:h-8 rounded-full border-2 border-white overflow-hidden"
                                                            title="{{ $supervisorLink->name }}">
                                                            <div
                                                                class="w-full h-full bg-[#7fabc9] flex items-center justify-center text-white text-xs font-medium">
                                                                {{ substr($supervisorLink->name, 0, 1) }}
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                                @if ($project->supervisorLinks->count() > 3)
                                                    <div
                                                        class="relative z-0 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 text-xs font-medium border-2 border-white">
                                                        +{{ $project->supervisorLinks->count() - 3 }}
                                                    </div>
                                                @endif
                                            </div>
                                            <span class="text-xs sm:text-sm text-gray-600 sm:ml-2 truncate">
                                                @foreach ($project->supervisorLinks->take(2) as $supervisorLink)
                                                    {{ $supervisorLink->name }}@if (!$loop->last), @endif
                                                @endforeach
                                                @if ($project->supervisorLinks->count() > 2)
                                                    <span
                                                        class="text-gray-500">+{{ $project->supervisorLinks->count() - 2 }}
                                                        more</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-0 pt-2 sm:divide-x sm:divide-gray-400">
                                        @if ($project->section)
                                            <div class="text-xs sm:text-sm text-gray-500 sm:pr-3">
                                                {{ $project->section->name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 sm:py-12">
                <p class="text-gray-600 text-base sm:text-lg">No projects available at the moment.</p>
            </div>
        @endif
    </div>
@endsection
