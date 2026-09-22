<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class OnboardingController extends Controller
{
    public function show(Request $request, string $token)
    {
        $user = User::where('invitation_token', $token)
            ->whereNull('email_verified_at')
            ->firstOrFail();

        // Check if invitation is expired (7 days)
        if ($user->invitation_sent_at && $user->invitation_sent_at->copy()->addDays(7)->isPast()) {
            abort(410, 'This invitation link has expired.');
        }

        $groups = Group::with('section')
            ->orderBy('name')
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name.' ('.$group->section->name.')',
                ];
            });

        return view('onboarding.show', [
            'user' => $user,
            'token' => $token,
            'groups' => $groups,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $user = User::where('invitation_token', $token)
            ->whereNull('email_verified_at')
            ->firstOrFail();

        // Check if invitation is expired (7 days)
        if ($user->invitation_sent_at && $user->invitation_sent_at->copy()->addDays(7)->isPast()) {
            return back()->withErrors(['token' => 'This invitation link has expired.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
            'group_id' => ['exists:groups,id', Rule::requiredIf(! $user->group_id)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        // Update password
        $user->password = Hash::make($validated['password']);

        // Update group
        if (isset($validated['group_id']) && $validated['group_id']) {
            $user->group_id = $validated['group_id'];
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            $user->avatar_url = $request->file('avatar')->store('avatars', 'public');
        }

        $user->activateAccount();
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(
            'password_hash_'.Auth::getDefaultDriver(),
            $user->getAuthPassword()
        );

        return redirect()->route('onboarding.welcome');
    }
}
