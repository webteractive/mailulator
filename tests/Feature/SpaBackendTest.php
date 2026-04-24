<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Attachment;
use Webteractive\Mailulator\Models\Inbox;

beforeEach(function () {
    $this->inbox = Inbox::query()->create([
        'name' => 'SPA Inbox',
        'api_key' => Inbox::hashToken(Str::random(40)),
    ]);

    // Mailulator::check defaults to local-env only. Force it open for these tests.
    Mailulator::auth(fn () => true);
    Mailulator::canViewInbox(fn () => true);
});

it('serves the SPA shell when gate passes', function () {
    $response = $this->get('/mailulator');

    $response->assertOk();
    expect($response->getContent())->toContain('MAILULATOR_CONFIG');
});

it('returns 403 when gate fails', function () {
    Mailulator::auth(fn () => false);

    $this->get('/mailulator')->assertForbidden();
});

it('lists inboxes visible to the user', function () {
    $hidden = Inbox::query()->create([
        'name' => 'Hidden',
        'api_key' => Inbox::hashToken(Str::random(40)),
    ]);

    Mailulator::canViewInbox(fn ($user, $inboxId) => $inboxId !== $hidden->id);

    $response = $this->getJson('/mailulator/api/inboxes');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids->all())->toBe([$this->inbox->id]);
});

it('toggles an email read state on POST /read', function () {
    $email = $this->inbox->emails()->create([
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'unread',
        'headers' => [],
        'created_at' => now(),
    ]);

    expect($email->read_at)->toBeNull();

    $this->postJson("/mailulator/api/emails/{$email->id}/read")->assertOk();
    expect($email->fresh()->read_at)->not->toBeNull();

    $this->postJson("/mailulator/api/emails/{$email->id}/read")->assertOk();
    expect($email->fresh()->read_at)->toBeNull();
});

it('preview route sends CSP sandbox headers', function () {
    $email = $this->inbox->emails()->create([
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'x',
        'html_body' => '<h1>Hi</h1>',
        'headers' => [],
        'created_at' => now(),
    ]);

    $response = $this->get("/mailulator/emails/{$email->id}/preview");

    $response->assertOk();
    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'none'");
    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('streams an attachment download', function () {
    Storage::fake('local');
    Storage::disk('local')->put('mailulator/test/file.txt', 'hello');

    $email = $this->inbox->emails()->create([
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'x',
        'headers' => [],
        'created_at' => now(),
    ]);

    $attachment = Attachment::query()->create([
        'email_id' => $email->id,
        'filename' => 'file.txt',
        'mime_type' => 'text/plain',
        'size' => 5,
        'disk' => 'local',
        'path' => 'mailulator/test/file.txt',
        'created_at' => now(),
    ]);

    $response = $this->get("/mailulator/emails/{$email->id}/attachments/{$attachment->id}");

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('file.txt');
});
