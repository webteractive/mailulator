<?php

namespace Webteractive\Mailulator\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webteractive\Mailulator\Models\Inbox;

class EnsureValidInboxToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $inbox = Inbox::query()->forToken($token)->first();

        if (! $inbox) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        app()->instance('mailulator.inbox', $inbox);
        $request->attributes->set('mailulator.inbox', $inbox);

        return $next($request);
    }
}
