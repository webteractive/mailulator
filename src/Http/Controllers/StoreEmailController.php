<?php

namespace Webteractive\Mailulator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webteractive\Mailulator\Actions\StoreIncomingEmail;
use Webteractive\Mailulator\Http\Requests\StoreEmailRequest;
use Webteractive\Mailulator\Models\Inbox;

class StoreEmailController extends Controller
{
    public function __invoke(StoreEmailRequest $request, StoreIncomingEmail $action): JsonResponse
    {
        /** @var Inbox $inbox */
        $inbox = app('mailulator.inbox');

        $email = $action($inbox, $request);

        return response()->json(['id' => $email->id], 201);
    }
}
