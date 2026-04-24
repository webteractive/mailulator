<?php

namespace Webteractive\Mailulator\Actions;

use Webteractive\Mailulator\Models\Inbox;

class MarkAllRead
{
    public function __invoke(Inbox $inbox): int
    {
        return $inbox->emails()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
