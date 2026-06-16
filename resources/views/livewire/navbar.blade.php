<nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            {{-- Logo on the left --}}
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center space-x-2 sm:space-x-4">
                    <img src="{{ asset('assets/logos/tue_logo.svg') }}" alt="TU/e Logo" class="h-6 sm:h-8 w-auto">
                    <div class="hidden sm:block border-l border-gray-300 h-8 pl-4">
                        <div class="flex flex-col text-tue-black">
                            <span class="text-sm font-bold text-primary leading-tight">Mechanical Engineering</span>
                            <span class="text-xs leading-tight text-tue-scarlet">Projects Portal</span>
                       
                        </div>
                    </div>
                </a>
            </div>

            {{-- Desktop Menu items on the right --}}
            <div class="hidden md:flex items-center space-x-6">
                {{-- Research Projects with dropdown --}}
                <div class="relative group">
                    <a href="{{ route('projects.index') }}" class="nav-link flex items-center">
                        Research Projects
                        <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </a>
                    {{-- Dropdown menu --}}
                    <div class="absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 border border-gray-200">
                        <div class="py-1">
                            @foreach ($divisions as $division)
                                <a href="{{ route('projects.division.' . $division->slug) }}" class="nav-dropdown-link">
                                    {{ $division->name }}
                                </a>
                            @endforeach
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="{{ route('projects.index', ['type' => 'bachelor_thesis']) }}" class="nav-dropdown-link">
                                Bachelor Thesis Projects
                            </a>
                            <a href="{{ route('projects.index', ['type' => 'master_thesis']) }}" class="nav-dropdown-link">
                                Master Thesis Projects
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="{{ route('projects.index', ['browse' => 1]) }}" class="nav-dropdown-link">
                                All Projects
                            </a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('projects.past') }}" class="nav-link">Past Projects</a>
                <a href="{{ route('contact') }}" class="nav-link">Contact</a>
                @auth
                    <a href="{{ url('/admin') }}" class="btn-primary-sm">To admin panel</a>
                @endauth
            </div>

            {{-- Mobile menu button --}}
            <button
                id="mobile-menu-toggle"
                onclick="toggleMobileMenu()"
                class="md:hidden p-2 rounded-md text-tue-gray-light hover:text-primary hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary"
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
                    <a href="{{ route('projects.division.' . $division->slug) }}" class="nav-mobile-link">
                        {{ $division->name }}
                    </a>
                @endforeach
                <a href="{{ route('projects.index', ['browse' => 1]) }}" class="nav-mobile-link">
                    All Projects
                </a>
                <a href="{{ route('projects.index', ['type' => 'bachelor_thesis']) }}" class="nav-mobile-link">
                    Bachelor Thesis Projects
                </a>
                <a href="{{ route('projects.index', ['type' => 'master_thesis']) }}" class="nav-mobile-link">
                    Master Thesis Projects
                </a>
                <a href="{{ route('projects.past') }}" class="nav-mobile-link">
                    Past Projects
                </a>
                <a href="{{ route('contact') }}" class="nav-mobile-link">
                    Contact
                </a>
                @auth
                    <a href="{{ url('/admin') }}" class="block px-4 py-3 text-base font-medium text-white bg-primary hover:bg-tue-red-dark rounded-md transition-colors">
                        Staff access
                    </a>
                @endauth
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
