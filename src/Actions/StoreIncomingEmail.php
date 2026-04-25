<?php

namespace Webteractive\Mailulator\Actions;

use Closure;
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
        return $this->persist($inbox, [
            'from' => $request->input('from'),
            'to' => $request->input('to', []),
            'cc' => $request->input('cc') ?: null,
            'bcc' => $request->input('bcc') ?: null,
            'subject' => (string) $request->input('subject', ''),
            'html_body' => $request->input('html_body'),
            'text_body' => $request->input('text_body'),
            'headers' => $request->parsedHeaders(),
        ], fn (Email $email) => $this->storeAttachments($email, $request));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array{filename: string, mime_type: string, content: string}>  $attachments
     */
    public function fromArray(Inbox $inbox, array $payload, array $attachments = []): Email
    {
        return $this->persist($inbox, [
            'from' => $payload['from'] ?? '',
            'to' => $payload['to'] ?? [],
            'cc' => ($payload['cc'] ?? null) ?: null,
            'bcc' => ($payload['bcc'] ?? null) ?: null,
            'subject' => (string) ($payload['subject'] ?? ''),
            'html_body' => $payload['html_body'] ?? null,
            'text_body' => $payload['text_body'] ?? null,
            'headers' => $payload['headers'] ?? [],
        ], fn (Email $email) => $this->storeJsonAttachments($email, $attachments));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function persist(Inbox $inbox, array $attributes, Closure $afterCreate): Email
    {
        return DB::connection(Mailulator::connectionName())->transaction(function () use ($inbox, $attributes, $afterCreate) {
            $email = $inbox->emails()->create([
                ...$attributes,
                'created_at' => now(),
            ]);

            $afterCreate($email);

            $inbox->touchLastUsed();

            EmailReceived::dispatch($email);

            return $email;
        });
    }

    protected function storeAttachments(Email $email, StoreEmailRequest $request): void
    {
        if ($request->isJson()) {
            $attachments = $request->input('attachments', []);

            if (is_array($attachments)) {
                $this->storeJsonAttachments($email, $attachments);
            }

            return;
        }

        $files = $request->file('attachments', []);

        if (is_array($files)) {
            $disk = $this->attachmentsDisk();

            foreach ($files as $file) {
                $this->storeUploadedFile($email, $file, $disk);
            }
        }
    }

    /**
     * @param  array<int, array{filename: string, mime_type: string, content: string}>  $attachments
     */
    protected function storeJsonAttachments(Email $email, array $attachments): void
    {
        if ($attachments === []) {
            return;
        }

        $disk = $this->attachmentsDisk();

        foreach ($attachments as $attachment) {
            $this->storeJsonAttachment($email, $attachment, $disk);
        }
    }

    protected function attachmentsDisk(): string
    {
        return (string) config('mailulator.receiver.storage.attachments_disk', 'local');
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
