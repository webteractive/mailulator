<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Webteractive\Mailulator\Models\Email;
use Webteractive\Mailulator\Models\Inbox;

beforeEach(function () {
    config()->set('mail.default', 'mailulator');
    config()->set('mail.mailers.mailulator', ['transport' => 'mailulator']);
    config()->set('mailulator.driver.url', 'https://receiver.test');
    config()->set('mailulator.driver.token', 'test-token');
    config()->set('mailulator.driver.timeout', 5);
});

it('POSTs serialized email to the configured url with bearer token', function () {
    config()->set('mailulator.driver.on_failure', 'log');
    Http::fake(['receiver.test/*' => Http::response(['id' => 1], 201)]);

    Mail::raw('Hello', function ($msg) {
        $msg->from('sender@example.com')
            ->to('user@example.com')
            ->cc('cc@example.com')
            ->subject('Test');
    });

    Http::assertSent(function ($request) {
        expect($request->url())->toBe('https://receiver.test/api/emails');
        expect($request->header('Authorization')[0])->toBe('Bearer test-token');

        $body = $request->data();
        expect($body['from'])->toBe('sender@example.com')
            ->and($body['to'])->toBe(['user@example.com'])
            ->and($body['cc'])->toBe(['cc@example.com'])
            ->and($body['subject'])->toBe('Test');

        return true;
    });
});

it('encodes attachments as base64 in JSON mode', function () {
    config()->set('mailulator.driver.on_failure', 'log');
    Http::fake(['receiver.test/*' => Http::response(['id' => 1], 201)]);

    Mail::send([], [], function ($msg) {
        $msg->from('a@b.com')
            ->to('c@d.com')
            ->subject('With attachment')
            ->text('body')
            ->attachData('binary-content', 'report.txt', ['mime' => 'text/plain']);
    });

    Http::assertSent(function ($request) {
        $body = $request->data();
        expect($body['attachments'])->toHaveCount(1);
        expect($body['attachments'][0]['filename'])->toBe('report.txt');
        expect(base64_decode($body['attachments'][0]['content']))->toBe('binary-content');

        return true;
    });
});

it('logs and swallows on failure when on_failure=log', function () {
    config()->set('mailulator.driver.on_failure', 'log');
    Http::fake(['receiver.test/*' => Http::response('down', 500)]);
    Log::spy();

    Mail::raw('Hi', fn ($m) => $m->from('a@b.com')->to('c@d.com')->subject('x'));

    Log::shouldHaveReceived('warning')
        ->with('[mailulator] delivery failed', Mockery::type('array'));
});

it('throws on failure when on_failure=throw', function () {
    config()->set('mailulator.driver.on_failure', 'throw');
    Http::fake(['receiver.test/*' => Http::response('down', 500)]);

    expect(fn () => Mail::raw('Hi', fn ($m) => $m->from('a@b.com')->to('c@d.com')->subject('x')))
        ->toThrow(TransportException::class);
});

it('stays silent on failure when on_failure=silent', function () {
    config()->set('mailulator.driver.on_failure', 'silent');
    Http::fake(['receiver.test/*' => Http::response('down', 500)]);
    Log::spy();

    Mail::raw('Hi', fn ($m) => $m->from('a@b.com')->to('c@d.com')->subject('x'));

    Log::shouldNotHaveReceived('warning');
});

it('delivers end-to-end through the mailulator ingest endpoint', function () {
    $plaintext = Str::random(40);
    $inbox = Inbox::query()->create([
        'name' => 'E2E',
        'api_key' => Inbox::hashToken($plaintext),
    ]);

    config()->set('mailulator.driver.url', 'https://receiver.test');
    config()->set('mailulator.driver.token', $plaintext);
    config()->set('mailulator.driver.on_failure', 'throw');

    // Capture the payload the driver emits, then replay it through the real
    // ingest route — single-process proxy for what HTTP would do in production.
    Http::fake(function ($request) use ($plaintext) {
        $response = $this->postJson('/api/emails', $request->data(), [
            'Authorization' => 'Bearer '.$plaintext,
        ]);

        return Http::response($response->json(), $response->status());
    });

    Mail::raw('Hello world', function ($msg) {
        $msg->from('sender@example.com')
            ->to('user@example.com')
            ->subject('E2E Test');
    });

    expect(Email::query()->count())->toBe(1);
    $email = Email::query()->first();
    expect($email->inbox_id)->toBe($inbox->id)
        ->and($email->subject)->toBe('E2E Test')
        ->and($email->from)->toBe('sender@example.com')
        ->and($email->to)->toBe(['user@example.com']);
});
