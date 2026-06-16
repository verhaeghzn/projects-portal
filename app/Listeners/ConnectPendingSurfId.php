<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * After a failed admin SSO attempt we store the SURF persistent ID in the session
 * (SamlController::handleAdminAuth). When the user then signs in with email + password,
 * this listener links that SURF identity to their account so SSO works next time.
 */
class ConnectPendingSurfId
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $pendingSurfId = session('saml_pending_surf_id');

        if (empty($pendingSurfId)) {
            return;
        }

        // Whatever the outcome, don't attempt this again for the same session.
        session()->forget('saml_pending_surf_id');

        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if ($user->surf_id === $pendingSurfId) {
            return;
        }

        // Don't steal a SURF identity that is already linked to another account.
        $alreadyLinked = User::where('surf_id', $pendingSurfId)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($alreadyLinked) {
            Log::warning('SAML Link: Pending SURF identity already linked to another user; skipping.', [
                'user_id' => $user->getKey(),
            ]);

            return;
        }

        $user->surf_id = $pendingSurfId;
        $user->save();

        Log::info('SAML Link: Connected pending SURF identity after password login.', [
            'user_id' => $user->getKey(),
        ]);
    }
}
