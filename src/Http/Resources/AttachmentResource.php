<?php

namespace Webteractive\Mailulator\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Webteractive\Mailulator\Models\Attachment;

/**
 * @mixin Attachment
 */
class AttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'download_url' => route('mailulator.attachments.download', [
                'email' => $this->email_id,
                'attachment' => $this->id,
            ]),
        ];
    }
}
