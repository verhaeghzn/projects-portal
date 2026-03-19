<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating the given user. Admin only.
     */
    public function start(User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('Administrator'), 403);
        abort_if($user->id === auth()->id(), 403, 'Cannot impersonate yourself.');
        abort_if($user->hasRole('Administrator'), 403, 'Cannot impersonate another administrator.');

        Session::put('impersonate_id', $user->id);
        Session::put('impersonator_id', auth()->id());

        return redirect('/admin');
    }

    /**
     * Leave impersonation: clear session keys and redirect to admin users list.
     */
    public function leave(): RedirectResponse
    {
        Session::forget(['impersonate_id', 'impersonator_id']);

        return redirect('/admin/users');
    }
}
