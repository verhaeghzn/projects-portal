@extends('layouts.app')

@section('title', 'Welcome to the Projects Portal')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-primary/10 to-primary/20 py-10 sm:py-14 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-xl">
            <div class="bg-gradient-to-r from-primary to-tue-donkerblauw rounded-t-lg px-6 sm:px-10 py-8 sm:py-10">
                <p class="text-white/80 text-sm uppercase tracking-wide mb-2">Your account is ready</p>
                <h1 class="text-3xl sm:text-4xl font-heading text-white mb-3">
                    Welcome, {{ $guide->firstName() }}
                </h1>
                <p class="text-lg text-white/90 max-w-2xl">
                    {{ $guide->intro() }}
                </p>
            </div>

            <div class="px-6 sm:px-10 py-8 sm:py-10 space-y-14">
                <p class="text-tue-gray text-base sm:text-lg">
                    You are signed in as a <strong class="text-tue-black">{{ $guide->roleHeading() }}</strong>.
                    Here is what you can do, in the order you will most likely need it.
                </p>

                @foreach($guide->features() as $index => $feature)
                    @include('onboarding.partials.step', [
                        'feature' => $feature,
                        'index' => $index,
                    ])
                @endforeach

                <section class="border-t border-gray-200 pt-10">
                    <h2 class="text-xl sm:text-2xl font-heading text-tue-black mb-3">How would you like to sign in next time?</h2>
                    <p class="text-tue-gray leading-relaxed mb-6">
                        Linking your TU/e account lets you use SURFconext — the same sign-in as other TU/e systems — instead of the password you just chose. You can always add this later from your profile.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3">
                        @if($guide->canLinkTueAccount())
                            <a href="{{ route('saml.link', ['return' => url('/admin')]) }}" class="btn-primary">
                                Configure "sign in with TU/e account"
                            </a>
                        @endif
                        <a href="{{ url('/admin') }}" @class(['btn-secondary' => $guide->canLinkTueAccount(), 'btn-primary' => ! $guide->canLinkTueAccount()])>
                            Continue without TU/e account
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-copy-url]').forEach(function (button) {
        button.addEventListener('click', function () {
            const url = button.dataset.copyUrl;
            const original = button.textContent;

            const mark = function (label) {
                button.textContent = label;
                window.setTimeout(function () {
                    button.textContent = original;
                }, 2000);
            };

            navigator.clipboard.writeText(url).then(function () {
                mark('Copied');
            }).catch(function () {
                const link = button.closest('div')?.parentElement?.querySelector('a');

                if (link) {
                    const range = document.createRange();
                    range.selectNodeContents(link);
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(range);
                }

                mark('Press Cmd+C or Ctrl+C');
            });
        });
    });
</script>
@endpush
