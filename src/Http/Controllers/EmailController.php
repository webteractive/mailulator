<?php

namespace Webteractive\Mailulator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webteractive\Mailulator\Actions\DeleteAllInInbox;
use Webteractive\Mailulator\Actions\MarkAllRead;
use Webteractive\Mailulator\Http\Resources\EmailResource;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Email;
use Webteractive\Mailulator\Models\Inbox;

class EmailController extends Controller
{
    public function index(Request $request, Inbox $inbox)
    {
        $this->ensureVisible($request, $inbox);

        $emails = $inbox->emails()
            ->search($request->string('search')->toString() ?: null)
            ->orderByDesc('created_at')
            ->cursorPaginate(50);

        return EmailResource::collection($emails);
    }

    public function show(Request $request, Email $email)
    {
        $this->ensureVisible($request, $email->inbox);

        $email->load('attachments');

        return new EmailResource($email);
    }

    public function read(Request $request, Email $email): JsonResponse
    {
        $this->ensureVisible($request, $email->inbox);

        $email->forceFill(['read_at' => $email->read_at ? null : now()])->save();

        return response()->json(['read_at' => $email->read_at?->toIso8601String()]);
    }

    public function destroy(Request $request, Email $email): JsonResponse
    {
        $this->ensureVisible($request, $email->inbox);

        $email->delete();

        return response()->json(['deleted' => true]);
    }

    public function markAllRead(Request $request, Inbox $inbox, MarkAllRead $action): JsonResponse
    {
        $this->ensureVisible($request, $inbox);

        return response()->json(['updated' => $action($inbox)]);
    }

    public function deleteAll(Request $request, Inbox $inbox, DeleteAllInInbox $action): JsonResponse
    {
        $this->ensureVisible($request, $inbox);

        return response()->json(['deleted' => $action($inbox)]);
    }

    protected function ensureVisible(Request $request, Inbox $inbox): void
    {
        if (! Mailulator::userCanViewInbox($request->user(), $inbox->id)) {
            throw new NotFoundHttpException;
        }
    }
}
