<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webteractive\Mailulator\Models\Attachment;
use Webteractive\Mailulator\Models\Email;
use Webteractive\Mailulator\Models\Inbox;

beforeEach(function () {
    $this->plaintext = Str::random(40);
    $this->inbox = Inbox::query()->forceCreate([
        'name' => 'Test Inbox',
        'api_key' => Inbox::hashToken($this->plaintext),
    ]);
});

it('stores a JSON email and returns 201 with the id', function () {
    $response = $this->postJson('/api/emails', [
        'from' => 'sender@example.com',
        'to' => ['user@example.com'],
        'subject' => 'Welcome',
        'html_body' => '<h1>Hi</h1>',
        'text_body' => 'Hi',
        'headers' => ['X-Mailer' => 'Laravel'],
        'attachments' => [
            [
                'filename' => 'hello.txt',
                'mime_type' => 'text/plain',
                'content' => base64_encode('hello world'),
            ],
        ],
    ], ['Authorization' => 'Bearer '.$this->plaintext]);

    $response->assertCreated();
    expect($response->json('id'))->toBeInt();

    $email = Email::query()->first();
    expect($email->from)->toBe('sender@example.com')
        ->and($email->to)->toBe(['user@example.com'])
        ->and($email->subject)->toBe('Welcome')
        ->and($email->html_body)->toBe('<h1>Hi</h1>')
        ->and($email->headers)->toBe(['X-Mailer' => 'Laravel']);

    expect(Attachment::query()->count())->toBe(1);
    $attachment = Attachment::query()->first();
    expect($attachment->filename)->toBe('hello.txt')
        ->and($attachment->size)->toBe(strlen('hello world'));
    Storage::disk('local')->assertExists($attachment->path);
});

it('stores a multipart email with uploaded attachments', function () {
    Storage::fake('local');

    $response = $this->post('/api/emails', [
        'from' => 'sender@example.com',
        'to' => ['user@example.com'],
        'subject' => 'Multipart',
        'headers' => json_encode(['X-Test' => '1']),
        'attachments' => [
            UploadedFile::fake()->createWithContent('report.txt', 'contents'),
        ],
    ], [
        'Authorization' => 'Bearer '.$this->plaintext,
        'Accept' => 'application/json',
    ]);

    $response->assertCreated();

    $email = Email::query()->first();
    expect($email->subject)->toBe('Multipart')
        ->and($email->headers)->toBe(['X-Test' => '1']);

    expect(Attachment::query()->count())->toBe(1);
    $attachment = Attachment::query()->first();
    expect($attachment->filename)->toBe('report.txt');
});

it('rejects requests without a bearer token with 401', function () {
    $this->postJson('/api/emails', [
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
    ])->assertUnauthorized();
});

it('rejects requests with an unknown token with 401', function () {
    $this->postJson('/api/emails', [
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
    ], ['Authorization' => 'Bearer nope-nope-nope'])->assertUnauthorized();
});

it('touches last_used_at on the inbox', function () {
    expect($this->inbox->last_used_at)->toBeNull();

    $this->postJson('/api/emails', [
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'x',
    ], ['Authorization' => 'Bearer '.$this->plaintext])->assertCreated();

    expect($this->inbox->fresh()->last_used_at)->not->toBeNull();
});

it('writes to the mailulator connection, not the default connection', function () {
    $this->postJson('/api/emails', [
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'x',
    ], ['Authorization' => 'Bearer '.$this->plaintext])->assertCreated();

    expect(DB::connection('mailulator')->table('emails')->count())->toBe(1);

    // The default connection has no 'emails' table at all; assert that.
    expect(
        DB::connection('testing')->getSchemaBuilder()->hasTable('emails')
    )->toBeFalse();
});
