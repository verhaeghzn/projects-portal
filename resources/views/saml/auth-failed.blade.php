@extends('errors.layout')

@section('title', 'Sign-in failed')
@section('code', '403')
@section('heading', 'Sign-in unsuccessful')
@section('message')
    We could not complete your sign-in via SURFconext. This is usually temporary — please try again in a moment.
@endsection

@section('actions')
    <a href="{{ $retryUrl }}" class="btn-primary text-center w-full sm:w-auto">
        Try again
    </a>
    <a href="{{ route('contact') }}" class="btn-secondary text-center w-full sm:w-auto">
        Contact us
    </a>
@endsection

@section('details')
    @if (! empty($diagnostics))
        <details class="group">
            <summary class="cursor-pointer text-tue-gray hover:text-tue-black select-none list-none inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>Technical details</span>
            </summary>

            <dl class="mt-3 space-y-1 font-mono text-[11px] leading-relaxed text-tue-gray break-all">
                @foreach ([
                    'ref' => 'Ref',
                    'at' => 'Time',
                    'stage' => 'Stage',
                    'guard' => 'Guard',
                    'codes' => 'Codes',
                    'error' => 'Error',
                    'acs_url' => 'ACS URL',
                    'return' => 'Return URL',
                ] as $key => $label)
                    @if (! empty($diagnostics[$key]))
                        <div class="flex gap-2">
                            <dt class="shrink-0 w-16 opacity-60">{{ $label }}</dt>
                            <dd>
                                @if (is_array($diagnostics[$key]))
                                    {{ implode(', ', $diagnostics[$key]) }}
                                @else
                                    {{ $diagnostics[$key] }}
                                @endif
                            </dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            <p class="mt-2 text-[10px] text-tue-gray/70">
                Quote the ref when contacting support or checking server logs.
            </p>
        </details>
    @endif
@endsection
