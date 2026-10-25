<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validation and error messages follow the language the merchant picked in
 * the app (it sends Accept-Language). Anything else keeps APP_LOCALE (Arabic).
 */
class SetRequestLocale
{
    private const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $preferred = $request->getPreferredLanguage(self::SUPPORTED);

        if ($request->headers->has('Accept-Language') && $preferred !== null) {
            app()->setLocale($preferred);
        }

        return $next($request);
    }
}
