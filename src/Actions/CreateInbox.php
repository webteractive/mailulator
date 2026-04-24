<?php

namespace Webteractive\Mailulator\Actions;

use Illuminate\Support\Str;
use Webteractive\Mailulator\Models\Inbox;

class CreateInbox
{
    /**
     * @return array{inbox: Inbox, plaintext_key: string}
     */
    public function __invoke(string $name, ?int $retentionDays = null): array
    {
        $plaintext = Str::random(40);

        $inbox = Inbox::query()->create([
            'name' => $name,
            'api_key' => Inbox::hashToken($plaintext),
            'retention_days' => $retentionDays,
        ]);

        return ['inbox' => $inbox, 'plaintext_key' => $plaintext];
    }
}
