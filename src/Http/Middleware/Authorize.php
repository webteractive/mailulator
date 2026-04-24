<?php

namespace Webteractive\Mailulator\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webteractive\Mailulator\Mailulator;

class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Mailulator::userCanManage($request->user())) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
