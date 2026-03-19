@php
    $impersonate = app('impersonate');
@endphp
@if($impersonate->isImpersonating())
    <div class="fi-topbar sticky top-0 z-40 flex w-full items-center justify-center gap-2 border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200">
        <span class="inline-flex items-center gap-1.5">
            <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998-2.5 7.5 7.5 0 0 1-14.998 2.5Z" />
            </svg>
            You are impersonating <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).
        </span>
        <a
            href="{{ route('impersonate.leave') }}"
            class="inline-flex items-center gap-1.5 rounded-md bg-amber-600 px-3 py-1.5 font-medium text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
        >
            <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
            </svg>
            Leave impersonation
        </a>
    </div>
@endif
