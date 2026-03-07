<nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            {{-- Logo on the left --}}
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center space-x-2 sm:space-x-4">
                    {{-- Disabled for now --}}
                    {{-- <img src="{{ asset('assets/logos/mom_colored.png') }}" alt="MoM Logo" class="h-10"> --}}
                    <img src="{{ asset('assets/logos/tue_logo.png') }}" alt="TU/e Logo" class="h-6 sm:h-8">
                    <div class="hidden sm:block border-l text-[#16537a] border-gray-300 h-8 pl-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-[#16537a] leading-tight">ME</span>
                            <span class="text-xs text-[#16537a] leading-tight">Projects Portal</span>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Desktop Menu items on the right --}}
            <div class="hidden md:flex items-center space-x-6">
                {{-- Research Projects with dropdown --}}
                <div class="relative group">
                    <a href="{{ route('projects.index') }}" class="text-gray-700 hover:text-[#7fabc9] px-3 py-2 text-sm font-medium font-sans flex items-center">
                        Research Projects
                        <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </a>
                    {{-- Dropdown menu --}}
                    <div class="absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 border border-gray-200">
                        <div class="py-1">
                            @foreach ($divisions as $division)
                                <a href="{{ route('projects.division.' . $division->slug) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#7fabc9] hover:text-white transition-colors">
                                    {{ $division->name }}
                                </a>
                            @endforeach
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="{{ route('projects.index', ['type' => 'bachelor_thesis']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#7fabc9] hover:text-white transition-colors">
                                Bachelor Thesis Projects
                            </a>
                            <a href="{{ route('projects.index', ['type' => 'master_thesis']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#7fabc9] hover:text-white transition-colors">
                                Master Thesis Projects
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="{{ route('projects.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#7fabc9] hover:text-white transition-colors">
                                All Projects
                            </a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('projects.past') }}" class="text-gray-700 hover:text-[#7fabc9] px-3 py-2 text-sm font-medium font-sans">Past Projects</a>
                <a href="{{ route('contact') }}" class="text-gray-700 hover:text-[#7fabc9] px-3 py-2 text-sm font-medium font-sans">Contact</a>
            </div>

            {{-- Mobile menu button --}}
            <button 
                id="mobile-menu-toggle" 
                onclick="toggleMobileMenu()" 
                class="md:hidden p-2 rounded-md text-gray-700 hover:text-[#7fabc9] hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#7fabc9]"
                aria-expanded="false"
                aria-controls="mobile-menu">
                <svg id="mobile-menu-icon-open" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                <svg id="mobile-menu-icon-close" class="h-6 w-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-menu" class="md:hidden border-t border-gray-200 py-4 hidden">
            <div class="space-y-1">
                @foreach ($divisions as $division)
                    <a href="{{ route('projects.division.' . $division->slug) }}" class="block px-4 py-3 text-base font-medium text-gray-700 hover:bg-[#7fabc9] hover:text-white rounded-md transition-colors">
                        {{ $division->name }}
                    </a>
                @endforeach
                <a href="{{ route('projects.index') }}" class="block px-4 py-3 text-base font-medium text-gray-700 hover:bg-[#7fabc9] hover:text-white rounded-md transition-colors">
                    All Projects
                </a>
                <a href="{{ route('projects.index', ['type' => 'bachelor_thesis']) }}" class="block px-4 py-3 text-base font-medium text-gray-700 hover:bg-[#7fabc9] hover:text-white rounded-md transition-colors">
                    Bachelor Thesis Projects
                </a>
                <a href="{{ route('projects.index', ['type' => 'master_thesis']) }}" class="block px-4 py-3 text-base font-medium text-gray-700 hover:bg-[#7fabc9] hover:text-white rounded-md transition-colors">
                    Master Thesis Projects
                </a>
                <a href="{{ route('projects.past') }}" class="block px-4 py-3 text-base font-medium text-gray-700 hover:bg-[#7fabc9] hover:text-white rounded-md transition-colors">
                    Past Projects
                </a>
                <a href="{{ route('contact') }}" class="block px-4 py-3 text-base font-medium text-gray-700 hover:bg-[#7fabc9] hover:text-white rounded-md transition-colors">
                    Contact
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        const toggle = document.getElementById('mobile-menu-toggle');
        const iconOpen = document.getElementById('mobile-menu-icon-open');
        const iconClose = document.getElementById('mobile-menu-icon-close');
        
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            iconOpen.classList.add('hidden');
            iconClose.classList.remove('hidden');
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            menu.classList.add('hidden');
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
        }
    }
</script>
