@extends('layouts.app')

@section('title', 'Past Projects')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
    <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-heading text-gray-900 mb-2 sm:mb-4">Past Projects</h1>
        <p class="text-base sm:text-lg text-gray-600">Completed research projects</p>
    </div>

    @if($projects->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
            @foreach($projects as $project)
                <a href="{{ route('projects.show', $project) }}" class="group bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    @if($project->featured_image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($project->featured_image) }}" alt="{{ $project->name }}" class="w-full h-40 sm:h-48 object-cover">
                    @else
                        <div class="w-full h-40 sm:h-48 bg-gradient-to-br from-[#7fabc9] to-[#5a8ba8] flex items-center justify-center">
                            <span class="text-white text-xl sm:text-2xl font-bold">{{ substr($project->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="p-4 sm:p-6">
                        <h2 class="text-base sm:text-lg lg:text-xl font-heading text-gray-900 mb-2 group-hover:text-[#7fabc9] transition-colors">{{ $project->name }}</h2>
                        <p class="text-gray-600 text-sm mb-3 sm:mb-4 line-clamp-3">{{ $project->short_description }}</p>
                        
                        @if($project->tags->count() > 0)
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($project->tags as $tag)
                                    @php
                                        $colorClasses = match($tag->category->value) {
                                            'group' => 'bg-blue-100 text-blue-800',
                                            'nature' => 'bg-green-100 text-green-800',
                                            'focus' => 'bg-amber-100 text-amber-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $colorClasses }}">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        
                        @if($project->supervisors->count() > 0)
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:space-x-2 pt-3 sm:pt-4 border-t border-gray-200">
                                <span class="text-xs sm:text-sm text-gray-500">Supervisors:</span>
                                <div class="flex items-center gap-2 sm:gap-0">
                                    <div class="flex -space-x-2">
                                        @foreach($project->supervisors->take(3) as $index => $supervisor)
                                            <div class="relative {{ $index === 0 ? 'z-30' : ($index === 1 ? 'z-20' : 'z-10') }} w-7 h-7 sm:w-8 sm:h-8 rounded-full border-2 border-white overflow-hidden" title="{{ $supervisor->name }}">
                                                @if($supervisor->avatar_url)
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($supervisor->avatar_url) }}" 
                                                         alt="{{ $supervisor->name }}" 
                                                         class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full bg-[#7fabc9] flex items-center justify-center text-white text-xs font-medium">
                                                        {{ substr($supervisor->name, 0, 1) }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if($project->supervisors->count() > 3)
                                            <div class="relative z-0 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 text-xs font-medium border-2 border-white">
                                                +{{ $project->supervisors->count() - 3 }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="text-xs sm:text-sm text-gray-600 sm:ml-2 truncate">
                                        @foreach($project->supervisors->take(2) as $supervisor)
                                            {{ $supervisor->name }}@if(!$loop->last), @endif
                                        @endforeach
                                        @if($project->supervisors->count() > 2)
                                            <span class="text-gray-500">+{{ $project->supervisors->count() - 2 }} more</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6 sm:mt-8">
            {{ $projects->links() }}
        </div>
    @else
        <div class="text-center py-8 sm:py-12">
            <p class="text-gray-600 text-base sm:text-lg">No past projects available.</p>
        </div>
    @endif
</div>
@endsection

