<?php

namespace Webteractive\Mailulator\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webteractive\Mailulator\Models\Email;

class EmailReceived implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Email $email) {}

    public function broadcastOn(): array
    {
        $realtime = config('mailulator.receiver.realtime', []);

        if (! ($realtime['enabled'] ?? true)) {
            return [];
        }

        if (($realtime['mode'] ?? 'polling') !== 'broadcast') {
            return [];
        }

        return [new PrivateChannel('mailulator.inbox.'.$this->email->inbox_id)];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->email->id,
            'inbox_id' => $this->email->inbox_id,
            'from' => $this->email->from,
            'subject' => $this->email->subject,
            'created_at' => optional($this->email->created_at)->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'email.received';
    }
}
