@php
    $hasShot = ! empty($feature['image']) && is_file(public_path($feature['image']));
@endphp
<section>
    <div class="lg:grid lg:grid-cols-2 lg:gap-x-10 lg:items-start">
        <div>
            <div class="flex items-center gap-3 min-h-8">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-white text-sm font-semibold">
                    {{ $index + 1 }}
                </span>
                <h2 class="text-xl sm:text-2xl font-heading text-tue-black">{{ $feature['title'] }}</h2>
            </div>

            <div class="sm:pl-11 mt-3 space-y-3">
                @foreach($feature['body'] as $paragraph)
                    <p class="text-tue-gray leading-relaxed">{{ $paragraph }}</p>
                @endforeach

                @if(! empty($feature['url']))
                    <div class="rounded-lg border border-primary/30 bg-primary/5 px-4 py-4 sm:px-5">
                        <p class="text-xs uppercase tracking-wide text-tue-gray mb-2">Bookmark this address</p>
                        <div class="flex flex-col gap-3">
                            <a href="{{ $feature['url'] }}" aria-label="{{ $feature['url'] }}" class="text-base sm:text-lg font-medium text-primary hover:underline">{{ \Illuminate\Support\Str::beforeLast($feature['url'], '/') }}/<br class="sm:hidden">{{ \Illuminate\Support\Str::afterLast($feature['url'], '/') }}</a>
                            <div>
                                <button type="button" class="btn-secondary" data-copy-url="{{ $feature['url'] }}">
                                    Copy link
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($hasShot)
            <img
                src="{{ asset($feature['image']) }}"
                alt="{{ $feature['imageAlt'] }}"
                class="mt-5 lg:mt-0 w-full h-auto rounded-lg border border-gray-200 shadow-sm self-start"
                loading="lazy"
            >
        @endif
    </div>
</section>
