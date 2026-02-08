<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectOldDomain
{
    /**
     * Redirect requests from the old production domain to the new domain (APP_URL),
     * preserving path and query string. Uses 301 (permanent).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $oldDomain = config('app.redirect_old_domain');

        if (empty($oldDomain)) {
            return $next($request);
        }

        $host = $request->getHost();
        if (strtolower($host) !== strtolower($oldDomain)) {
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
