@if($showSection)
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
    <div class="fi-section-content p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('SURF Conext') }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($isConnected)
                        {{ __('Your admin account is linked to SURF Conext. You can sign in with "Sign in with SURF Conext" on the login page.') }}
                    @else
                        {{ __('Your admin account is not linked to SURF Conext. Use "Link SURF Conext" in the user menu (top right) to connect.') }}
                    @endif
                </p>
            </div>
            @if($isConnected)
                <x-filament::button
                    color="danger"
                    variant="outlined"
                    icon="heroicon-m-arrow-right-on-rectangle"
                    wire:click="disconnect"
                    wire:confirm="{{ __('Are you sure you want to disconnect SURF Conext? You can link it again later from the user menu.') }}"
                >
                    {{ __('Disconnect SURF Conext') }}
                </x-filament::button>
            @endif
        </div>
    </div>
</div>
@endif
