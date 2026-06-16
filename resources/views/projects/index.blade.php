@extends('layouts.app')

@section('title')
    @if (!empty($selectedDivision) && !empty($selectedDivision['name']))
        Research Projects – {{ $selectedDivision['name'] }}
    @else
        Research Projects
    @endif
@endsection

@section('content')
    @if (!$manualBrowse)
        <div class="project-search flex flex-col min-h-[calc(100vh-4rem)] bg-gradient-to-b from-gray-50/80 to-white">
            @if ($searchQuery === '')
                {{-- Empty state: centered hero --}}
                <div class="flex-1 flex flex-col items-center justify-center px-4 sm:px-6 py-12 sm:py-20">
                    <div class="w-full max-w-2xl text-center">
                        <h1 class="text-3xl sm:text-4xl font-heading text-gray-900 mb-3 tracking-tight">
                            Find your research project
                            @if (!empty($selectedDivision))
                                within {{ $selectedDivision['abbrev'] ?? $selectedDivision['name'] }}
                            @endif
                        </h1>
                        <p class="text-base sm:text-lg text-gray-500 mb-8 sm:mb-10">
                            Describe what you're looking for — topic, supervisor, or type of work.
                        </p>

                        @include('projects.partials.search-form')

                        <div class="mt-6 flex flex-wrap justify-center gap-2 sm:gap-3">
                            @foreach (['Experiments with steel', 'Supervised by a specific professor', 'Master thesis on simulation'] as $suggestion)
                                <button type="button" data-search-suggestion="{{ $suggestion }}"
                                    class="search-suggestion-btn px-4 py-2 rounded-full border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white">
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>

                        <p class="mt-8">
                            <a href="{{ $manualBrowseUrl }}"
                                class="text-sm text-gray-400 hover:text-[#7fabc9] transition-colors">
                                or browse projects manually
                            </a>
                        </p>
                    </div>
                </div>
            @else
                {{-- Results state: conversation + projects --}}
                <div class="flex-1 w-full">
                    {{-- Assistant response --}}
                    <div class="border-b border-gray-100">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-6 flex gap-3 sm:gap-4">
                            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-[#16537a] flex items-center justify-center text-white text-[10px] sm:text-xs font-bold select-none mt-0.5"
                                aria-hidden="true">ME</div>
                            <div class="flex-1 min-w-0">
                                @if (!empty($smartSearchError))
                                    <p class="text-sm sm:text-base text-amber-800 leading-relaxed" role="status">
                                        {{ $smartSearchError }}
                                    </p>
                                @elseif (!empty($smartSearchSummary))
                                    <p class="text-sm sm:text-base text-gray-800 leading-relaxed" role="status">
                                        {{ $smartSearchSummary }}
                                    </p>
                                @else
                                    <p class="text-sm sm:text-base text-gray-500 leading-relaxed" role="status">
                                        Searching for matching projects…
                                    </p>
                                @endif

                                @if ($projects->count() > 0)
                                    <p class="mt-2 text-sm text-gray-500">
                                        Found {{ $projects->count() }} {{ Str::plural('project', $projects->count()) }}.
                                    </p>
                                @elseif (empty($smartSearchError))
                                    <p class="mt-2 text-sm text-gray-500">
                                        No matching projects found. Try broadening your search or browse manually.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                    @if (config('app.debug') && !empty($smartSearchDebug))
                        <details class="mb-8 text-left border border-dashed border-gray-300 rounded-xl bg-white/60">
                            <summary class="cursor-pointer px-4 py-2.5 text-xs font-medium text-gray-500 select-none">
                                Developer debug
                            </summary>
                            <div class="border-t border-dashed border-gray-300 px-4 py-3 space-y-4 text-xs font-mono leading-relaxed overflow-x-auto">
                                <div>
                                    <p class="font-sans font-semibold text-gray-600 mb-1">Model</p>
                                    <p class="text-gray-800">{{ $smartSearchDebug['model'] }}</p>
                                </div>
                                <div>
                                    <p class="font-sans font-semibold text-gray-600 mb-1">Instructions (system)</p>
                                    <pre class="whitespace-pre-wrap break-words text-gray-800">{{ $smartSearchDebug['instructions'] }}</pre>
                                </div>
                                <div>
                                    <p class="font-sans font-semibold text-gray-600 mb-1">User message (projects + query)</p>
                                    <pre class="whitespace-pre-wrap break-words text-gray-800">{{ json_encode($smartSearchDebug['user_message'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                                @if (!empty($smartSearchDebug['structured_response']))
                                    <div>
                                        <p class="font-sans font-semibold text-gray-600 mb-1">Structured response (raw)</p>
                                        <pre class="whitespace-pre-wrap break-words text-gray-800">{{ json_encode($smartSearchDebug['structured_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-sans font-semibold text-gray-600 mb-1">Applied criteria (after validation)</p>
                                    <pre class="whitespace-pre-wrap break-words text-gray-800">{{ json_encode($smartSearchDebug['applied_criteria'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        </details>
                    @endif

                    @if ($projects->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
                            @foreach ($projects as $project)
                                @include('projects.partials.project-card', ['project' => $project])
                            @endforeach
                        </div>
                    @endif
                    </div>
                </div>

                {{-- Sticky bottom search bar --}}
                <div class="sticky bottom-0 z-50 isolate border-t border-gray-200 bg-white px-4 sm:px-6 py-3 shadow-[0_-4px_24px_rgba(0,0,0,0.08)]">
                    <div class="max-w-2xl mx-auto">
                        @include('projects.partials.search-form', ['compact' => true])
                        <p class="mt-2 text-center">
                            <a href="{{ $manualBrowseUrl }}"
                                class="text-xs text-gray-400 hover:text-[#7fabc9] transition-colors">
                                or browse projects manually
                            </a>
                        </p>
                    </div>
                </div>
            @endif
        </div>

        @push('scripts')
            <script>
                (function () {
                    function setSearchSuggestionsDisabled(disabled) {
                        document.querySelectorAll('[data-search-suggestion]').forEach(function (btn) {
                            btn.disabled = disabled;
                        });
                    }

                    function setProjectSearchLoading(form, loading) {
                        if (!form) {
                            return;
                        }

                        form.dataset.searchLoading = loading ? 'true' : 'false';
                        form.setAttribute('aria-busy', loading ? 'true' : 'false');

                        const textarea = form.querySelector('.project-search-q');
                        if (textarea) {
                            textarea.readOnly = loading;
                        }

                        const submitBtn = form.querySelector('.search-submit-btn');
                        if (submitBtn) {
                            submitBtn.disabled = loading;
                        }

                        form.querySelector('.project-search-type-toggle')?.classList.toggle('pointer-events-none', loading);
                        form.querySelector('.project-search-type-toggle')?.classList.toggle('opacity-60', loading);

                        form.querySelector('.search-submit-icon')?.classList.toggle('hidden', loading);
                        form.querySelector('.search-submit-spinner')?.classList.toggle('hidden', !loading);
                        form.querySelector('.search-loading-status')?.classList.toggle('hidden', !loading);
                        form.querySelector('.project-search-input-wrap')?.classList.toggle('opacity-60', loading);
                    }

                    function beginProjectSearch(form) {
                        if (!form || form.dataset.searchLoading === 'true') {
                            return false;
                        }

                        const query = form.querySelector('[name="q"]')?.value.trim() ?? '';
                        if (query === '') {
                            return false;
                        }

                        document.querySelectorAll('[data-project-search-form]').forEach(function (f) {
                            setProjectSearchLoading(f, true);
                        });
                        setSearchSuggestionsDisabled(true);

                        return true;
                    }

                    document.querySelectorAll('[data-project-search-form]').forEach(function (form) {
                        form.addEventListener('submit', function (e) {
                            if (!beginProjectSearch(form)) {
                                e.preventDefault();
                            }
                        });

                        form.querySelector('.project-search-q')?.addEventListener('keydown', function (e) {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                if (form.dataset.searchLoading === 'true') {
                                    return;
                                }
                                form.requestSubmit();
                            }
                        });
                    });

                    document.querySelectorAll('[data-search-suggestion]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            const form = document.querySelector('[data-project-search-form]');
                            if (!form || form.dataset.searchLoading === 'true') {
                                return;
                            }

                            const input = form.querySelector('.project-search-q');
                            if (!input) {
                                return;
                            }

                            input.value = btn.dataset.searchSuggestion;
                            form.requestSubmit();
                        });
                    });
                })();
            </script>
        @endpush
    @else
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
            <div class="mb-6 sm:mb-8">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-heading text-gray-900 mb-2 sm:mb-4">
                    Research Projects
                    @if (!empty($selectedDivision) && !empty($selectedDivision['name']))
                        – {{ $selectedDivision['name'] }}
                    @endif
                </h1>
                <p class="text-base sm:text-lg text-gray-600 mb-4 sm:mb-6">
                    Explore available research opportunities
                    @if (!empty($selectedSupervisorName))
                        supervised by {{ $selectedSupervisorName }}
                    @endif
                </p>

                {{-- Mobile filter toggle button --}}
                <button id="filter-toggle"
                    class="sm:hidden w-full flex items-center justify-between px-4 py-3 mb-3 bg-gray-50 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#7fabc9] focus:ring-offset-2 transition-colors"
                    onclick="toggleFilters()" aria-expanded="false" aria-controls="filter-section">
                    <span>Filters</span>
                    <svg id="filter-toggle-icon" class="h-5 w-5 text-gray-500 transition-transform duration-200"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    function toggleFilters() {
                        const filterSection = document.getElementById('filter-section');
                        const toggleButton = document.getElementById('filter-toggle');
                        const toggleIcon = document.getElementById('filter-toggle-icon');

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
                        const type = document.getElementById('type-filter').value;
                        const section = document.getElementById('section-filter').value;
                        const supervisor = document.getElementById('supervisor-filter')?.value || '';
                        const groupEl = document.getElementById('group-filter');
                        const group = groupEl ? groupEl.value : '';

                        const params = new URLSearchParams();
                        params.set('browse', '1');
                        if (type) params.set('type', type);
                        if (section) params.set('section', section);
                        if (supervisor) params.set('supervisor', supervisor);
                        if (group) params.set('group', group);

                        const queryString = params.toString();
                        window.location.href = window.location.pathname + (queryString ? '?' + queryString : '');
                    }
                </script>
            </div>

        @if ($projects->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
                @foreach ($projects as $project)
                    @include('projects.partials.project-card', ['project' => $project])
                @endforeach
            </div>
        @else
            <div class="text-center py-8 sm:py-12">
                <p class="text-gray-600 text-base sm:text-lg">No projects available at the moment.</p>
            </div>
        @endif
    </div>
    @endif
@endsection
