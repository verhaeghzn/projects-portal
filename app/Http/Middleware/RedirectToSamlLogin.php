<?php

namespace App\Http\Middleware;

use App\Helpers\SamlHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToSamlLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!SamlHelper::isLoginRequired()) {
            return $next($request);
        }

        // Get the current path and URI
        $path = $request->path();
        $uri = $request->getRequestUri();
        $routeName = $request->route()?->getName();
        
        // Don't redirect SAML routes themselves or onboarding routes
        // Check multiple ways to be absolutely sure
        if (
            $path === 'saml/login' ||
            $path === 'saml/acs' ||
            $path === 'saml/logout' ||
            $path === 'saml/sls' ||
            $path === 'saml/metadata' ||
            str_starts_with($path, 'saml/') ||
            str_starts_with($path, 'onboarding/') ||
            str_starts_with($uri, '/saml/') ||
            str_starts_with($uri, '/onboarding/') ||
            ($routeName && (str_starts_with($routeName, 'saml.') || str_starts_with($routeName, 'onboarding.')))
        ) {
            return $next($request);
        }

        // Check if user is authenticated with students guard
        if (!auth('students')->check()) {
            // Use the current URL as return, but avoid loops
            $returnUrl = $request->url();
            
            // Prevent redirect loops - if return URL contains saml/login, use home
            if (str_contains($returnUrl, 'saml/login') || str_contains($returnUrl, '/saml/')) {
                $returnUrl = url('/');
            }
            
            return redirect('/saml/login?guard=students&return=' . urlencode($returnUrl));
        }

        return $next($request);
    }
}
