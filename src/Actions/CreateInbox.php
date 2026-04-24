<?php

namespace Webteractive\Mailulator\Actions;

use Illuminate\Support\Str;
use Webteractive\Mailulator\Models\Inbox;

class CreateInbox
{
    /**
     * @param  array<string, mixed>|null  $settings
     * @return array{inbox: Inbox, plaintext_key: string}
     */
    public function __invoke(string $name, ?int $retentionDays = null, ?array $settings = null): array
    {
        $plaintext = Str::random(40);

        $inbox = Inbox::query()->create([
            'name' => $name,
            'api_key' => Inbox::hashToken($plaintext),
            'retention_days' => $retentionDays,
            'settings' => $settings,
        ]);

        return ['inbox' => $inbox, 'plaintext_key' => $plaintext];
    }
}
