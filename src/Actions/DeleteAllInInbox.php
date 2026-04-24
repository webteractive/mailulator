<?php

namespace Webteractive\Mailulator\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Webteractive\Mailulator\Models\Email;
use Webteractive\Mailulator\Models\Inbox;

class DeleteAllInInbox
{
    public function __invoke(Inbox $inbox): int
    {
        $count = 0;

        $inbox->emails()
            ->with('attachments')
            ->chunkById(100, function ($emails) use (&$count): void {
                /** @var Collection<int, Email> $emails */
                foreach ($emails as $email) {
                    foreach ($email->attachments as $attachment) {
                        Storage::disk($attachment->disk)->delete($attachment->path);
                    }
                    $email->delete();
                    $count++;
                }
            });

        return $count;
    }
}
