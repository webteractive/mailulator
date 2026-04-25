<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webteractive\Mailulator\Jobs\PruneEmails;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Attachment;
use Webteractive\Mailulator\Models\Email;
use Webteractive\Mailulator\Models\Inbox;

beforeEach(function () {
    Mailulator::auth(fn () => true);
    Mailulator::canViewInbox(fn () => true);
    Mailulator::manage(fn () => true);

    $this->inbox = Inbox::query()->forceCreate([
        'name' => 'QoL',
        'api_key' => Inbox::hashToken(Str::random(40)),
        'retention_days' => 7,
    ]);
});

it('searches across subject, from, and to', function () {
    $matches = $this->inbox->emails()->create([
        'from' => 'alice@example.com',
        'to' => ['team@acme.com'],
        'subject' => 'Welcome',
        'headers' => [],
        'created_at' => now(),
    ]);

    $nope = $this->inbox->emails()->create([
        'from' => 'bob@other.com',
        'to' => ['x@y.com'],
        'subject' => 'Unrelated',
        'headers' => [],
        'created_at' => now(),
    ]);

    $response = $this->getJson("/mailulator/api/inboxes/{$this->inbox->id}/emails?search=alice");
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids->all())->toBe([$matches->id]);

    $response = $this->getJson("/mailulator/api/inboxes/{$this->inbox->id}/emails?search=Welcome");
    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$matches->id]);

    $response = $this->getJson("/mailulator/api/inboxes/{$this->inbox->id}/emails?search=acme");
    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$matches->id]);
});

it('mark all read updates every unread email in the inbox', function () {
    $this->inbox->emails()->create(['from' => 'a@b.com', 'to' => ['x@y.com'], 'subject' => 'a', 'headers' => [], 'created_at' => now()]);
    $this->inbox->emails()->create(['from' => 'a@b.com', 'to' => ['x@y.com'], 'subject' => 'b', 'headers' => [], 'created_at' => now()]);
    $this->inbox->emails()->create(['from' => 'a@b.com', 'to' => ['x@y.com'], 'subject' => 'c', 'headers' => [], 'read_at' => now(), 'created_at' => now()]);

    $this->postJson("/mailulator/api/inboxes/{$this->inbox->id}/mark-read")
        ->assertOk()
        ->assertJson(['updated' => 2]);

    expect($this->inbox->emails()->whereNull('read_at')->count())->toBe(0);
});

it('delete all removes emails and their attachment files from disk', function () {
    Storage::fake('local');
    Storage::disk('local')->put('mailulator/1/file.txt', 'bytes');

    $email = $this->inbox->emails()->create([
        'from' => 'a@b.com',
        'to' => ['x@y.com'],
        'subject' => 'x',
        'headers' => [],
        'created_at' => now(),
    ]);

    Attachment::query()->create([
        'email_id' => $email->id,
        'filename' => 'file.txt',
        'mime_type' => 'text/plain',
        'size' => 5,
        'disk' => 'local',
        'path' => 'mailulator/1/file.txt',
        'created_at' => now(),
    ]);

    $this->deleteJson("/mailulator/api/inboxes/{$this->inbox->id}/emails")
        ->assertOk()
        ->assertJson(['deleted' => 1]);

    expect(Email::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing('mailulator/1/file.txt');
});

it('prunes emails older than each inbox retention_days', function () {
    $keep = $this->inbox->emails()->create([
        'from' => 'a@b.com', 'to' => ['x@y.com'], 'subject' => 'recent', 'headers' => [],
        'created_at' => now()->subDays(3),
    ]);
    $kill = $this->inbox->emails()->create([
        'from' => 'a@b.com', 'to' => ['x@y.com'], 'subject' => 'old', 'headers' => [],
        'created_at' => now()->subDays(30),
    ]);

    $pruned = (new PruneEmails)->handle();

    expect($pruned)->toBe(1);
    expect(Email::query()->find($keep->id))->not->toBeNull();
    expect(Email::query()->find($kill->id))->toBeNull();
});

it('prune skips inboxes with null retention_days', function () {
    $other = Inbox::query()->forceCreate([
        'name' => 'forever',
        'api_key' => Inbox::hashToken(Str::random(40)),
        'retention_days' => null,
    ]);
    $ancient = $other->emails()->create([
        'from' => 'a@b.com', 'to' => ['x@y.com'], 'subject' => 'ancient', 'headers' => [],
        'created_at' => now()->subYears(5),
    ]);

    expect((new PruneEmails)->handle())->toBe(0);
    expect(Email::query()->find($ancient->id))->not->toBeNull();
});
