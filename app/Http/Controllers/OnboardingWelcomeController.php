<?php

namespace App\Http\Controllers;

use App\Onboarding\WelcomeGuide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingWelcomeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect('/admin/login');
        }

        $user->loadMissing('group.section.division');

        return view('onboarding.welcome', [
            'user' => $user,
            'guide' => WelcomeGuide::for($user),
        ]);
    }
}
