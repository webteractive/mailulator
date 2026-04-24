<?php

namespace Webteractive\Mailulator\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webteractive\Mailulator\Mailulator;

class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Mailulator::check($request)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
