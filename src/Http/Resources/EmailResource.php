<?php

namespace Webteractive\Mailulator\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Webteractive\Mailulator\Models\Email;

/**
 * @mixin Email
 */
class EmailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'inbox_id' => $this->inbox_id,
            'from' => $this->from,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'subject' => $this->subject,
            'has_html' => (bool) $this->html_body,
            'has_text' => (bool) $this->text_body,
            'text_body' => $this->when($request->routeIs('mailulator.api.emails.show'), $this->text_body),
            'headers' => $this->when($request->routeIs('mailulator.api.emails.show'), $this->headers),
            'preview_url' => route('mailulator.emails.preview', $this->id),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'read_at' => optional($this->read_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
