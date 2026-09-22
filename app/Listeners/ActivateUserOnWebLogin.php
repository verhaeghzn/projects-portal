<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Completing the invitation form is not the only way staff sign in.
 * Admin SSO and password login also prove the account belongs to them,
 * so mark those users as activated instead of leaving them pending.
 */
class ActivateUserOnWebLogin
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if ($user->email_verified_at !== null && $user->invitation_token === null) {
            return;
        }

        $user->activateAccount()->save();
    }
}
