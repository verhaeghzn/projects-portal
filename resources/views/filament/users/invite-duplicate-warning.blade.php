<div style="display: flex; gap: 0.75rem; align-items: flex-start;">
    <x-filament::icon
        icon="heroicon-o-exclamation-triangle"
        style="flex: none; margin-top: 0.125rem; color: var(--warning-600);"
    />

    <div style="min-width: 0; font-size: 0.875rem; line-height: 1.5;">
        <p style="margin: 0; font-weight: 600; color: var(--gray-950);">Check before inviting</p>
        <p style="margin: 0.125rem 0 0; color: var(--gray-600);">
            This creates a new account. If this person already has one, even under a different email, inviting them again creates a duplicate.
        </p>

        @if ($matches->isNotEmpty())
            <ul style="list-style: none; margin: 0.75rem 0 0; padding: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                @foreach ($matches as $match)
                    <li style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; background: var(--gray-50); box-shadow: inset 0 0 0 1px color-mix(in oklab, var(--gray-950) 10%, transparent);">
                        <span style="min-width: 0;">
                            <span style="display: block; font-weight: 600; color: var(--gray-950);">{{ $match->name }}</span>
                            <span style="display: block; color: var(--gray-500);">
                                {{ $match->email }}@if ($match->group) · {{ $match->group->name }}@endif
                            </span>
                        </span>
                        @if (\App\Filament\Users\InviteDuplicateWarning::isSameEmail($match, $email))
                            <x-filament::badge color="danger" size="sm">Already registered</x-filament::badge>
                        @else
                            <x-filament::badge :color="\App\Filament\Users\InviteDuplicateWarning::statusColor($match)" size="sm">
                                {{ \App\Filament\Users\InviteDuplicateWarning::statusLabel($match) }}
                            </x-filament::badge>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if ($matches->count() === 8)
                <p style="margin: 0.5rem 0 0; color: var(--gray-500);">Showing the first 8 matches.</p>
            @endif
        @endif
    </div>
</div>
