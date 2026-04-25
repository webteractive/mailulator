<?php

namespace Webteractive\Mailulator\Actions;

use Illuminate\Support\Str;
use InvalidArgumentException;
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

        $inbox = Inbox::query()->forceCreate([
            'name' => $name,
            'api_key' => Inbox::hashToken($plaintext),
            'retention_days' => $retentionDays,
            'settings' => $this->sanitizeSettings($settings),
        ]);

        return ['inbox' => $inbox, 'plaintext_key' => $plaintext];
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, mixed>|null
     */
    protected function sanitizeSettings(?array $settings): ?array
    {
        if ($settings === null) {
            return null;
        }

        $color = $settings['color'] ?? null;

        if ($color === null) {
            return null;
        }

        if (! is_string($color) || ! preg_match(Inbox::COLOR_REGEX, $color)) {
            throw new InvalidArgumentException('Inbox color must be a 6-digit hex string (e.g. #a1b2c3).');
        }

        return ['color' => $color];
    }
}
