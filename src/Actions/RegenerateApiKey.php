<?php

namespace Webteractive\Mailulator\Actions;

use Illuminate\Support\Str;
use Webteractive\Mailulator\Models\Inbox;

class RegenerateApiKey
{
    public function __invoke(Inbox $inbox): string
    {
        $plaintext = Str::random(40);

        $inbox->forceFill(['api_key' => Inbox::hashToken($plaintext)])->save();

        return $plaintext;
    }
}
