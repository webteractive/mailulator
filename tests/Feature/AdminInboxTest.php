<?php

use Illuminate\Support\Str;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Inbox;

beforeEach(function () {
    Mailulator::auth(fn () => true);
    Mailulator::canViewInbox(fn () => true);
    Mailulator::manage(fn () => true);
});

it('creates an inbox and returns a plaintext key shown once', function () {
    $response = $this->postJson('/mailulator/api/inboxes', [
        'name' => 'Staging',
        'retention_days' => 30,
    ]);

    $response->assertCreated();

    $plaintext = $response->json('plaintext_key');
    expect($plaintext)->toBeString()->toHaveLength(40);

    $inboxId = $response->json('inbox.id');
    $inbox = Inbox::query()->findOrFail($inboxId);
    expect($inbox->name)->toBe('Staging')
        ->and($inbox->retention_days)->toBe(30)
        ->and($inbox->api_key)->toBe(Inbox::hashToken($plaintext));
});

it('regenerating a key invalidates the old token at the ingest boundary', function () {
    $oldPlaintext = Str::random(40);
    $inbox = Inbox::query()->create([
        'name' => 'Inbox',
        'api_key' => Inbox::hashToken($oldPlaintext),
    ]);

    $response = $this->postJson("/mailulator/api/inboxes/{$inbox->id}/regenerate-key");
    $response->assertOk();

    $newPlaintext = $response->json('plaintext_key');
    expect($newPlaintext)->not->toBe($oldPlaintext);

    // Old token no longer works
    $this->postJson('/api/emails', [
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'x',
    ], ['Authorization' => 'Bearer '.$oldPlaintext])->assertUnauthorized();

    // New token does
    $this->postJson('/api/emails', [
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'x',
    ], ['Authorization' => 'Bearer '.$newPlaintext])->assertCreated();
});

it('forbids non-admin users from admin endpoints', function () {
    Mailulator::manage(fn () => false);

    $this->postJson('/mailulator/api/inboxes', ['name' => 'Nope'])->assertForbidden();
});

it('updates inbox name and retention', function () {
    $inbox = Inbox::query()->create([
        'name' => 'Old',
        'api_key' => Inbox::hashToken(Str::random(40)),
    ]);

    $this->patchJson("/mailulator/api/inboxes/{$inbox->id}", [
        'name' => 'New',
        'retention_days' => 7,
    ])->assertOk();

    $inbox->refresh();
    expect($inbox->name)->toBe('New')
        ->and($inbox->retention_days)->toBe(7);
});

it('deletes an inbox and cascades emails', function () {
    $inbox = Inbox::query()->create([
        'name' => 'Temp',
        'api_key' => Inbox::hashToken(Str::random(40)),
    ]);
    $inbox->emails()->create([
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'x',
        'headers' => [],
        'created_at' => now(),
    ]);

    $this->deleteJson("/mailulator/api/inboxes/{$inbox->id}")->assertOk();

    expect(Inbox::query()->count())->toBe(0);
});
