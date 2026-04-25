<?php

namespace Webteractive\Mailulator\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webteractive\Mailulator\Events\EmailReceived;
use Webteractive\Mailulator\Http\Requests\StoreEmailRequest;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Attachment;
use Webteractive\Mailulator\Models\Email;
use Webteractive\Mailulator\Models\Inbox;

class StoreIncomingEmail
{
    public function __invoke(Inbox $inbox, StoreEmailRequest $request): Email
    {
        return DB::connection(Mailulator::connectionName())->transaction(function () use ($inbox, $request) {
            $email = $inbox->emails()->create([
                'from' => $request->input('from'),
                'to' => $request->input('to', []),
                'cc' => $request->input('cc') ?: null,
                'bcc' => $request->input('bcc') ?: null,
                'subject' => (string) $request->input('subject', ''),
                'html_body' => $request->input('html_body'),
                'text_body' => $request->input('text_body'),
                'headers' => $request->parsedHeaders(),
                'created_at' => now(),
            ]);

            $this->storeAttachments($email, $request);

            $inbox->touchLastUsed();

            EmailReceived::dispatch($email);

            return $email;
        });
    }

    protected function storeAttachments(Email $email, StoreEmailRequest $request): void
    {
        $attachments = $request->input('attachments', []);
        $files = $request->file('attachments', []);
        $disk = (string) config('mailulator.receiver.storage.attachments_disk', 'local');

        if ($request->isJson() && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                $this->storeJsonAttachment($email, $attachment, $disk);
            }

            return;
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                $this->storeUploadedFile($email, $file, $disk);
            }
        }
    }

    protected function storeJsonAttachment(Email $email, array $attachment, string $disk): void
    {
        $bytes = base64_decode($attachment['content'] ?? '', true);

        if ($bytes === false) {
            return;
        }

        $path = $this->attachmentPath($email, $attachment['filename']);
        Storage::disk($disk)->put($path, $bytes);

        Attachment::query()->create([
            'email_id' => $email->id,
            'filename' => $attachment['filename'],
            'mime_type' => $attachment['mime_type'],
            'size' => strlen($bytes),
            'disk' => $disk,
            'path' => $path,
            'created_at' => now(),
        ]);
    }

    protected function storeUploadedFile(Email $email, UploadedFile $file, string $disk): void
    {
        $path = $this->attachmentPath($email, $file->getClientOriginalName());
        Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        Attachment::query()->create([
            'email_id' => $email->id,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'disk' => $disk,
            'path' => $path,
            'created_at' => now(),
        ]);
    }

    protected function attachmentPath(Email $email, string $filename): string
    {
        return sprintf(
            'mailulator/%d/%s-%s',
            $email->id,
            Str::random(8),
            $filename,
        );
    }
}
