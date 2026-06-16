<form method="GET" action="{{ url()->current() }}" role="search" aria-label="Search projects"
    class="project-search-form" data-project-search-form>
    @php
        $activeThesisType = $selectedThesisType ?? request('type', 'master_thesis');
        $compact = $compact ?? false;
    @endphp
    <div class="flex justify-center gap-2 {{ $compact ? 'mb-2' : 'mb-3' }} project-search-type-toggle" role="group" aria-label="Project type">
        <label class="cursor-pointer">
            <input type="radio" name="type" value="bachelor_thesis" class="sr-only peer"
                {{ $activeThesisType === 'bachelor_thesis' ? 'checked' : '' }}>
            <span class="inline-block px-4 py-1.5 rounded-full text-sm border transition-colors
                peer-checked:bg-[#16537a] peer-checked:text-white peer-checked:border-[#16537a]
                peer-disabled:opacity-50 peer-disabled:cursor-not-allowed
                border-gray-200 bg-white text-gray-600 hover:border-gray-300">
                Bachelor
            </span>
        </label>
        <label class="cursor-pointer">
            <input type="radio" name="type" value="master_thesis" class="sr-only peer"
                {{ $activeThesisType === 'master_thesis' ? 'checked' : '' }}>
            <span class="inline-block px-4 py-1.5 rounded-full text-sm border transition-colors
                peer-checked:bg-[#16537a] peer-checked:text-white peer-checked:border-[#16537a]
                peer-disabled:opacity-50 peer-disabled:cursor-not-allowed
                border-gray-200 bg-white text-gray-600 hover:border-gray-300">
                Master
            </span>
        </label>
    </div>
    <div class="project-search-input-wrap relative {{ $compact ? 'rounded-2xl' : 'rounded-3xl' }} border border-gray-200 bg-white shadow-[0_2px_12px_rgba(0,0,0,0.08)] focus-within:border-gray-300 focus-within:shadow-[0_2px_16px_rgba(0,0,0,0.12)] transition-shadow">
        <label for="project-search-q" class="sr-only">Search projects</label>
        <textarea id="project-search-q" name="q" rows="{{ $compact ? 1 : 2 }}"
            placeholder="Ask anything about available projects…"
            autocomplete="off"
            class="project-search-q w-full {{ $compact ? 'px-4 pt-2.5 pb-9 text-sm min-h-[2.75rem]' : 'px-5 pt-4 pb-12 text-base min-h-[4.5rem]' }} text-gray-800 placeholder:text-gray-400 bg-transparent border-0 {{ $compact ? 'rounded-2xl' : 'rounded-3xl' }} resize-none focus:ring-0 focus:outline-none read-only:opacity-60 read-only:cursor-not-allowed">{{ $searchQuery ?? '' }}</textarea>
        <button type="submit"
            class="search-submit-btn absolute {{ $compact ? 'bottom-2 right-2 w-8 h-8' : 'bottom-3 right-3 w-9 h-9' }} !min-h-0 !min-w-0 rounded-full bg-[#7fabc9] hover:bg-[#6a9ab8] text-white flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-[#7fabc9] focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-[#7fabc9]"
            aria-label="Search">
            <svg class="search-submit-icon {{ $compact ? 'h-3.5 w-3.5' : 'h-4 w-4' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
            </svg>
            <svg class="search-submit-spinner hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </div>
    <p class="search-loading-status hidden mt-3 text-sm text-gray-500 text-center" role="status" aria-live="polite">
        <span class="inline-flex items-center gap-2">
            <svg class="h-4 w-4 animate-spin text-[#7fabc9]" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Searching for matching projects…
        </span>
    </p>
</form>
