@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
{{-- Full-width hero --}}
<div class="relative min-h-[40vh] sm:min-h-[50vh] flex items-end overflow-hidden">
    <img
        src="{{ asset('assets/images/hero-me-lab.png') }}"
        alt="Laser diagnostics experiment in a Mechanical Engineering lab"
        class="absolute inset-0 w-full h-full object-cover object-center"
        loading="eager"
        fetchpriority="high"
    >
    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/45 to-black/20"></div>
    <div class="absolute bottom-0 left-0 w-1/3 h-full bg-gradient-to-r from-black/50 to-transparent pointer-events-none" aria-hidden="true"></div>

    <div class="relative w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="max-w-3xl">
            <div class="w-12 h-1 bg-primary mb-4 sm:mb-6" aria-hidden="true"></div>
            <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-heading text-white mb-3 sm:mb-4 tracking-tight">
                Welcome to the Mechanical Engineering Projects Portal
            </h1>
            <p class="text-base sm:text-lg text-gray-200 mb-6 sm:mb-8 max-w-2xl">
                Welcome to the projects portal of the Department of Mechanical Engineering at Eindhoven University of Technology!
            </p>
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                <a href="{{ route('projects.index') }}" class="btn-primary-hero">
                    Browse Projects
                </a>
                <a href="{{ route('contact') }}" class="btn-secondary-hero">
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <div class="max-w-4xl mx-auto mb-8 sm:mb-12 text-center">
        <p class="text-sm sm:text-base md:text-lg text-tue-gray leading-relaxed">
            The Department of Mechanical Engineering is organized into three research divisions: <strong class="text-tue-black">Thermo-Fluids Engineering (TFE)</strong>, <strong class="text-tue-black">Computational and Experimental Mechanics (CEM)</strong>, and <strong class="text-tue-black">Dynamical Systems Design (DSD)</strong>. This portal showcases research projects across the department—bachelor and master thesis opportunities—so you can find projects that match your interests.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 items-stretch">
        @foreach ($divisions as $division)
            <a href="{{ route('projects.division.' . $division->slug) }}" class="group text-left p-5 sm:p-6 bg-gray-50 rounded-lg border border-gray-100 shadow-sm hover:shadow-md hover:bg-gray-100 transition-all duration-200 flex flex-col min-h-0">
                <h2 class="text-xl sm:text-2xl font-heading text-tue-black mb-2 group-hover:text-primary transition-colors">{{ $division->name }}</h2>
                <p class="text-sm sm:text-base text-tue-gray mb-4 flex-1 leading-relaxed">
                    @switch($division->slug)
                        @case('thermo-fluids-engineering')
                            Energy technology and power & flow research.
                            @break
                        @case('computational-experimental-mechanics')
                            Materials, manufacturing, and microsystems.
                            @break
                        @case('dynamical-systems-design')
                            Control systems, dynamics, and robotics.
                            @break
                        @default
                            Research projects in this division.
                    @endswitch
                </p>
                <span class="text-primary group-hover:text-tue-red-dark font-medium text-sm sm:text-base">
                    View Projects →
                </span>
            </a>
        @endforeach
    </div>

    <p class="text-center mt-8 sm:mt-10">
        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-sm sm:text-base font-medium link-primary">
            View all projects of ME →
        </a>
    </p>
</div>
@endsection
