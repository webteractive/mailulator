<?php

namespace Webteractive\Mailulator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webteractive\Mailulator\Actions\CreateInbox;
use Webteractive\Mailulator\Actions\RegenerateApiKey;
use Webteractive\Mailulator\Http\Resources\InboxResource;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Inbox;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $inboxes = Inbox::query()
            ->withCount(['emails as unread_count' => fn ($q) => $q->whereNull('read_at')])
            ->orderBy('name')
            ->get()
            ->filter(fn (Inbox $inbox) => Mailulator::userCanViewInbox($user, $inbox->id))
            ->values();

        return InboxResource::collection($inboxes);
    }

    public function store(Request $request, CreateInbox $create): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'retention_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $create($data['name'], $data['retention_days'] ?? null);

        return response()->json([
            'inbox' => new InboxResource($result['inbox']),
            'plaintext_key' => $result['plaintext_key'],
        ], 201);
    }

    public function update(Request $request, Inbox $inbox): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'retention_days' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $inbox->fill($data)->save();

        return response()->json(['inbox' => new InboxResource($inbox)]);
    }

    public function destroy(Inbox $inbox): JsonResponse
    {
        $inbox->delete();

        return response()->json(['deleted' => true]);
    }

    public function regenerateKey(Inbox $inbox, RegenerateApiKey $action): JsonResponse
    {
        $plaintext = $action($inbox);

        return response()->json([
            'inbox' => new InboxResource($inbox->refresh()),
            'plaintext_key' => $plaintext,
        ]);
    }
}
