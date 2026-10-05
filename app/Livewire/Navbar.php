<?php

namespace App\Livewire;

use App\Auth\StudentsUser;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Component;

class Navbar extends Component
{
    public function render()
    {
        return view('livewire.navbar', [
            'showAdminPanelLink' => $this->showAdminPanelLink(),
        ]);
    }

    /**
     * Staff already signed in on the admin guard always see the link.
     * A public (student) SURF session sees it when its persistent ID is linked
     * to a staff account that can open the admin panel.
     */
    private function showAdminPanelLink(): bool
    {
        if (auth('web')->check()) {
            return true;
        }

        $student = auth('students')->user();

        if (! $student instanceof StudentsUser || $student->getPersistentId() === '') {
            return false;
        }

        $staff = User::query()->where('surf_id', $student->getPersistentId())->first();

        if (! $staff) {
            return false;
        }

        return $staff->canAccessPanel(Filament::getPanel('admin'));
    }
}
