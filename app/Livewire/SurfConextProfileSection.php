<?php

namespace App\Livewire;

use App\Helpers\SamlHelper;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SurfConextProfileSection extends Component
{
    public static function getSort(): int
    {
        return 50;
    }

    public function disconnect(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $user->surf_id = null;
        $user->save();

        Notification::make()
            ->title(__('SURF Conext account disconnected. You can link it again from the user menu.'))
            ->success()
            ->send();
    }

    public function render(): View
    {
        if (! SamlHelper::isEnabled()) {
            return view('livewire.surf-conext-profile-section')->with(['showSection' => false]);
        }

        /** @var User|null $user */
        $user = Auth::user();
        $isConnected = $user instanceof User && ! empty($user->surf_id);

        return view('livewire.surf-conext-profile-section', [
            'showSection' => true,
            'isConnected' => $isConnected,
        ]);
    }
}
