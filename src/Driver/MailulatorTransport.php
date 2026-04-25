<?php

namespace Webteractive\Mailulator\Driver;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;
use Throwable;
use Webteractive\Mailulator\Actions\StoreIncomingEmail;
use Webteractive\Mailulator\Models\Inbox;

class MailulatorTransport extends AbstractTransport
{
    public function __construct(
        protected string $url,
        protected string $token,
        protected int $timeout = 5,
        protected string $onFailure = 'log',
        protected bool $internal = false,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();

        if (! $original instanceof Message) {
            $this->handleFailure('original message is not a Symfony\\Component\\Mime\\Message');

            return;
        }

        $email = MessageConverter::toEmail($original);

        if ($this->internal) {
            $this->deliverInternally($email);

            return;
        }

        try {
            $payload = $this->buildPayload($email) + ['attachments' => $this->buildAttachments($email)];

            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post(rtrim($this->url, '/').'/api/emails', $payload);

            if ($response->failed()) {
                $this->handleFailure(sprintf('HTTP %d from %s', $response->status(), $this->url));
            }
        } catch (Throwable $e) {
            $this->handleFailure($e->getMessage(), $e);
        }
    }

    protected function deliverInternally(Email $email): void
    {
        try {
            $inbox = Inbox::query()->where('is_default', true)->first();

            if (! $inbox) {
                $this->handleFailure('no default inbox available for internal delivery');

                return;
            }

            app(StoreIncomingEmail::class)->fromArray(
                $inbox,
                $this->buildPayload($email),
                $this->buildAttachments($email),
            );
        } catch (Throwable $e) {
            $this->handleFailure($e->getMessage(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(Email $email): array
    {
        return [
            'from' => ($email->getFrom()[0] ?? null)?->getAddress() ?? '',
            'to' => array_map(fn ($a) => $a->getAddress(), $email->getTo()),
            'cc' => array_map(fn ($a) => $a->getAddress(), $email->getCc()),
            'bcc' => array_map(fn ($a) => $a->getAddress(), $email->getBcc()),
            'subject' => (string) $email->getSubject(),
            'html_body' => $email->getHtmlBody(),
            'text_body' => $email->getTextBody(),
            'headers' => $this->extractHeaders($email),
        ];
    }

    /**
     * @return array<int, array{filename: string, mime_type: string, content: string}>
     */
    protected function buildAttachments(Email $email): array
    {
        return array_map(fn ($attachment) => [
            'filename' => $attachment->getFilename() ?? 'attachment',
            'mime_type' => $attachment->getContentType(),
            'content' => base64_encode($attachment->getBody()),
        ], $email->getAttachments());
    }

    /**
     * @return array<string, string>
     */
    protected function extractHeaders(Email $email): array
    {
        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            $headers[$header->getName()] = $header->getBodyAsString();
        }

        return $headers;
    }

    protected function handleFailure(string $reason, ?Throwable $previous = null): void
    {
        match ($this->onFailure) {
            'throw' => throw new TransportException('[mailulator] delivery failed: '.$reason, 0, $previous),
            'silent' => null,
            default => Log::warning('[mailulator] delivery failed', [
                'reason' => $reason,
                'url' => $this->url,
            ]),
        };
    }

    public function __toString(): string
    {
        return 'mailulator';
    }
}
