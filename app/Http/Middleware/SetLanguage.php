<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        $previousLocale = app()->getLocale();
        app()->setLocale($request->cookie('ndk_language') === 'my' ? 'my' : 'en');

        try {
            return $next($request);
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}
