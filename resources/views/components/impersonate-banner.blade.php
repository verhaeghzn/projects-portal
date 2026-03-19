@php
use STS\FilamentImpersonate\Facades\Impersonation;
@endphp
@if(Impersonation::isImpersonating())
<div id="impersonate-banner-app" class="fixed top-0 left-0 right-0 z-50 flex items-center justify-center gap-5 py-3 bg-gray-800 text-gray-100 border-b border-gray-700 shadow-md">
    <span class="text-sm font-medium">
        You are impersonating: <strong>{{ auth()->user()?->name ?? auth()->user()?->email ?? 'User' }}</strong>
    </span>
    <a href="{{ route('filament-impersonate.leave') }}" class="px-4 py-2 text-sm font-medium rounded-md bg-gray-600 text-white hover:bg-gray-500 transition-colors">
        Leave impersonation
    </a>
</div>
<div class="h-12"></div>
@endif
