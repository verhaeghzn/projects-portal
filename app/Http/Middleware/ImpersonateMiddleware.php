<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateMiddleware
{
    /**
     * If impersonation is active, set the authenticated user to the impersonated user for this request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $impersonateId = session('impersonate_id');

        if ($impersonateId !== null) {
            $user = User::find($impersonateId);
            if ($user !== null) {
                auth()->setUser($user);
            }
        }

        return $next($request);
    }
}
