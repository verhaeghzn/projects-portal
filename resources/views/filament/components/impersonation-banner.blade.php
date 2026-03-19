@php
    $impersonateId = session('impersonate_id');
    $impersonatedUser = $impersonateId ? \App\Models\User::find($impersonateId) : null;
@endphp
@if($impersonatedUser)
    <div class="fi-impersonation-banner bg-amber-500 text-amber-950 dark:bg-amber-600 dark:text-amber-100 px-4 py-2.5 text-center text-sm font-medium shadow-sm">
        <span>You are impersonating <strong>{{ $impersonatedUser->name }}</strong> ({{ $impersonatedUser->email }}).</span>
        <a href="{{ route('admin.impersonate.leave') }}" class="ml-2 underline font-semibold hover:no-underline focus:outline-none focus:ring-2 focus:ring-amber-800 dark:focus:ring-amber-200 rounded">
            Exit impersonation
        </a>
    </div>
@endif
