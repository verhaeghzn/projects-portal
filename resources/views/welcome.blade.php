@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
@php
    $divisions = config('divisions', []);
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
    <div class="text-center mb-8 sm:mb-12">
        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-heading text-gray-900 mb-3 sm:mb-4 px-2">Welcome to the Mechanical Engineering Projects Portal</h1>
        <p class="text-base sm:text-lg md:text-xl text-gray-600 mb-4 sm:mb-6 px-2">
            Welcome to the projects portal of the Department of Mechanical Engineering at Eindhoven University of Technology!
        </p>
        <div class="max-w-4xl mx-auto mb-6 sm:mb-8 px-2">
            <p class="text-sm sm:text-base md:text-lg text-gray-700 leading-relaxed">
                The Department of Mechanical Engineering is organized into three research divisions: <strong>Thermo-Fluids Engineering (TFE)</strong>, <strong>Computational and Experimental Mechanics (CEM)</strong>, and <strong>Dynamical Systems Design (DSD)</strong>. This portal showcases research projects across the department—bachelor and master thesis opportunities—so you can find projects that match your interests.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row justify-center gap-3 sm:gap-4 px-2">
            <a href="{{ route('projects.index') }}" class="bg-[#7fabc9] text-white px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-[#5a8ba8] transition-colors text-sm sm:text-base">
                Browse Projects
            </a>
            <a href="{{ route('contact') }}" class="bg-white text-[#7fabc9] border-2 border-[#7fabc9] px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm sm:text-base">
                Contact Us
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 mt-8 sm:mt-12 lg:mt-16 items-stretch">
        @foreach ($divisions as $division)
            <a href="{{ route('projects.division.' . $division['slug']) }}" class="group text-left p-5 sm:p-6 bg-gray-50 rounded-lg border border-gray-100 shadow-sm hover:shadow-md hover:bg-gray-100 transition-all duration-200 flex flex-col min-h-0">
                <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-2 group-hover:text-[#7fabc9] transition-colors">{{ $division['name'] }}</h2>
                <p class="text-sm sm:text-base text-gray-600 mb-4 flex-1 leading-relaxed">
                    @switch($division['slug'])
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
                <span class="text-[#7fabc9] group-hover:text-[#5a8ba8] font-medium text-sm sm:text-base">
                    View Projects →
                </span>
            </a>
        @endforeach
    </div>

    <p class="text-center mt-8 sm:mt-10">
        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-sm sm:text-base font-medium text-[#7fabc9] hover:text-[#5a8ba8] hover:underline transition-colors">
            View all projects of ME →
        </a>
    </p>
</div>
@endsection
