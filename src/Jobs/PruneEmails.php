<?php

namespace Webteractive\Mailulator\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Webteractive\Mailulator\Models\Email;
use Webteractive\Mailulator\Models\Inbox;

class PruneEmails implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): int
    {
        $pruned = 0;

        Inbox::query()
            ->whereNotNull('retention_days')
            ->get()
            ->each(function (Inbox $inbox) use (&$pruned) {
                $cutoff = now()->subDays($inbox->retention_days);

                $inbox->emails()
                    ->with('attachments')
                    ->where('created_at', '<', $cutoff)
                    ->chunkById(100, function ($emails) use (&$pruned): void {
                        /** @var Collection<int, Email> $emails */
                        foreach ($emails as $email) {
                            foreach ($email->attachments as $attachment) {
                                Storage::disk($attachment->disk)->delete($attachment->path);
                            }
                            $email->delete();
                            $pruned++;
                        }
                    });
            });

        return $pruned;
    }
}
