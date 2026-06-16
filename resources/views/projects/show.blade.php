@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
        <div class="mb-6 sm:mb-8">
            <a href="{{ route('projects.index') }}"
                class="text-primary hover:text-tue-red-dark text-sm font-medium mb-3 sm:mb-4 inline-block">
                ← Back to Projects
            </a>

            @if ($project->featured_image)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($project->featured_image) }}" alt="{{ $project->name }}"
                    class="w-full object-cover rounded-lg mb-4 sm:mb-6">
            @endif

            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-heading text-gray-900 mb-3 sm:mb-4">{{ $project->name }}</h1>

            <div class="flex flex-wrap items-center gap-2 sm:gap-4 mb-4 sm:mb-6">
                @foreach ($project->types as $type)
                    <span
                        class="inline-flex items-center px-2.5 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-primary text-white">
                        {{ $type->name }}
                    </span>
                @endforeach
                @if ($project->tags->count() > 0)
                    <div class="flex flex-wrap gap-1.5 sm:gap-2">
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
            </div>
        </div>

        <article class="prose prose-slate prose-sm sm:prose-base max-w-none mb-8 sm:mb-12">
            {!! $project->richtext_content !!}
        </article>

        @if ($project->supervisorLinks->count() > 0)
            @php
                // Collect all groups and sections from all supervisors, maintaining order by supervisor ranking
                $groups = collect();
                $sections = collect();
                $seenGroupIds = [];
                $seenSectionIds = [];

                foreach ($project->supervisorLinks as $supervisorLink) {
                    if (!$supervisorLink->isExternal() && $supervisorLink->supervisor) {
                        $supervisor = $supervisorLink->supervisor;
                        
                        // Add group if supervisor has one and we haven't seen it yet
                        if ($supervisor->group && !in_array($supervisor->group->id, $seenGroupIds)) {
                            $groups->push($supervisor->group);
                            $seenGroupIds[] = $supervisor->group->id;
                        }
                        
                        // Add section if supervisor's group has one and we haven't seen it yet
                        if ($supervisor->group && $supervisor->group->section && !in_array($supervisor->group->section->id, $seenSectionIds)) {
                            $sections->push($supervisor->group->section);
                            $seenSectionIds[] = $supervisor->group->section->id;
                        }
                    }
                }
            @endphp
            <div class="border-t border-gray-200 pt-6 sm:pt-8 mt-8 sm:mt-12">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
                    {{-- Left: Supervisor List --}}
                    <div class="space-y-3 sm:space-y-4">
                        @foreach ($project->supervisorLinks as $index => $supervisorLink)
                            @php
                                $supervisor = $supervisorLink->supervisor;
                                $isExternal = $supervisorLink->isExternal();
                                $supervisorName = $supervisorLink->name;
                            @endphp
                            <div class="flex items-center space-x-3 sm:space-x-4">
                                @if (!$isExternal && $supervisor && $supervisor->avatar_url)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($supervisor->avatar_url) }}"
                                        alt="{{ $supervisorName }}"
                                        class="w-10 h-10 sm:w-12 sm:h-12 md:w-15 md:h-15 rounded-full object-cover flex-shrink-0">
                                @else
                                    <div
                                        class="w-10 h-10 sm:w-12 sm:h-12 md:w-15 md:h-15 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-base sm:text-lg flex-shrink-0">
                                        {{ substr($supervisorName, 0, 1) }}
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                        <h3 class="font-semibold text-gray-900 text-sm sm:text-base break-words">
                                            {{ $supervisorName }}</h3>
                                    </div>
                                    @if (!$isExternal && $supervisor && $supervisor->email)
                                        <a href="#"
                                            class="obfuscated-email text-primary hover:text-tue-red-dark text-xs sm:text-sm break-all"
                                            data-encoded="{{ bin2hex($supervisor->email) }}">
                                            {{ $supervisor->email }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Right: Details --}}
                    <div>
                        <h3 class="text-lg sm:text-xl font-heading text-gray-900 mb-3 sm:mb-4">Details</h3>
                        <div class="space-y-2.5 sm:space-y-3">
                            <div>
                                <span class="text-xs sm:text-sm font-medium text-gray-600">Project Number:</span>
                                <div class="flex items-center gap-2">
                                    <span id="project-number"
                                        class="text-gray-900 text-xs sm:text-sm sm:text-base font-mono select-all bg-gray-100 px-2 py-1 rounded border border-gray-300 cursor-pointer hover:bg-gray-200 transition"
                                        onclick="navigator.clipboard.writeText('{{ $project->project_number }}'); 
                                            document.getElementById('copied-badge').classList.remove('hidden'); 
                                            setTimeout(function(){ document.getElementById('copied-badge').classList.add('hidden'); }, 1200);"
                                        title="Click to copy">
                                        {{ $project->project_number }}
                                    </span>
                                    <span id="copied-badge"
                                        class="hidden text-xs text-green-600 font-semibold bg-green-100 px-2 py-0.5 rounded transition">Copied!</span>

                                </div>
                            </div>
                            @if ($project->organization)
                                <div>
                                    <span class="text-xs sm:text-sm font-medium text-gray-600">Organization:</span>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if ($project->organization->logo)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($project->organization->logo) }}"
                                                alt="{{ $project->organization->name }}"
                                                class="w-6 h-6 sm:w-8 sm:h-8 object-contain flex-shrink-0">
                                        @endif
                                        @if ($project->organization->url)
                                            <a href="{{ $project->organization->url }}" target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-primary hover:text-tue-red-dark text-xs sm:text-sm break-words">
                                                {{ $project->organization->name }}
                                            </a>
                                        @else
                                            <p class="text-gray-900 text-xs sm:text-sm break-words">
                                                {{ $project->organization->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @if ($groups->count() > 0)
                                <div>
                                    <span class="text-xs sm:text-sm font-medium text-gray-600">Group{{ $groups->count() > 1 ? 's' : '' }}:</span>
                                    <div class="text-gray-900 text-xs sm:text-sm sm:text-base">
                                        @foreach ($groups as $group)
                                            @if ($group->external_url)
                                                <a href="{{ $group->external_url }}" target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-primary hover:text-tue-red-dark text-xs sm:text-sm sm:text-base">
                                                    {{ $group->name }}
                                                </a>
                                            @else
                                                <span>{{ $group->name }}</span>
                                            @endif
                                            @if (!$loop->last)
                                                <span class="text-gray-400">, </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if ($sections->count() > 0)
                                <div>
                                    <span class="text-xs sm:text-sm font-medium text-gray-600">Section{{ $sections->count() > 1 ? 's' : '' }}:</span>
                                    <div class="text-gray-900 text-xs sm:text-sm sm:text-base">
                                        @foreach ($sections as $section)
                                            <span>{{ $section->name }}</span>
                                            @if (!$loop->last)
                                                <span class="text-gray-400">, </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
