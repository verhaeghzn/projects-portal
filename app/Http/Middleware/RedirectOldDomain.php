<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectOldDomain
{
    /**
     * Redirect requests from the old production domain to the new domain (APP_URL),
     * preserving path and query string (subdomain is not retained). Uses 301 (permanent).
     *
     * REDIRECT_OLD_DOMAIN can be:
     * - Exact host: "cem-projects-dev.multiscale.nl" — only that host matches.
     * - Leading dot (subdomains): ".multiscale.nl" — "multiscale.nl" and any *.multiscale.nl match.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $oldDomain = config('app.redirect_old_domain');

        if (empty($oldDomain)) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $pattern = strtolower($oldDomain);

        $matches = str_starts_with($pattern, '.')
            ? ($host === ltrim($pattern, '.') || str_ends_with($host, $pattern))
            : ($host === $pattern);

        if (! $matches) {
            return $next($request);
        }

        $baseUrl = rtrim(config('app.url'), '/');
        $path = $request->path();
        $url = $path !== '' ? $baseUrl . '/' . $path : $baseUrl;
        $query = $request->getQueryString();
        if ($query !== null && $query !== '') {
            $url .= '?' . $query;
        }

        return redirect()->away($url, Response::HTTP_MOVED_PERMANENTLY);
    }
}
