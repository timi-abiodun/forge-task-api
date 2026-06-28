<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SwaggerDocsHeaderMiddleware
{
    /**
     * Add headers useful for loading swagger UI assets.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Allow swagger-ui to fetch /openapi.json from the same origin.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}

