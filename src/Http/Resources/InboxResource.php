<?php

namespace Webteractive\Mailulator\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Webteractive\Mailulator\Models\Inbox;

/**
 * @mixin Inbox
 */
class InboxResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'retention_days' => $this->retention_days,
            'last_used_at' => optional($this->last_used_at)->toIso8601String(),
            'unread_count' => $this->when(isset($this->unread_count), $this->unread_count),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
