<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webteractive\Mailulator\Events\EmailReceived;
use Webteractive\Mailulator\Models\Inbox;

beforeEach(function () {
    $this->plaintext = Str::random(40);
    $this->inbox = Inbox::query()->create([
        'name' => 'RT',
        'api_key' => Inbox::hashToken($this->plaintext),
    ]);
});

it('dispatches EmailReceived on ingest regardless of realtime mode', function () {
    Event::fake([EmailReceived::class]);

    $this->postJson('/api/emails', [
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'x',
    ], ['Authorization' => 'Bearer '.$this->plaintext])->assertCreated();

    Event::assertDispatched(EmailReceived::class);
});

it('broadcasts on private channel when enabled=true and mode=broadcast', function () {
    config()->set('mailulator.receiver.realtime.enabled', true);
    config()->set('mailulator.receiver.realtime.mode', 'broadcast');

    $email = $this->inbox->emails()->create([
        'from' => 'a@b.com', 'to' => ['c@d.com'], 'subject' => 'x',
        'headers' => [], 'created_at' => now(),
    ]);

    $channels = (new EmailReceived($email))->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0]->name)->toBe('private-mailulator.inbox.'.$this->inbox->id);
});

it('does not broadcast when mode=polling', function () {
    config()->set('mailulator.receiver.realtime.enabled', true);
    config()->set('mailulator.receiver.realtime.mode', 'polling');

    $email = $this->inbox->emails()->create([
        'from' => 'a@b.com', 'to' => ['c@d.com'], 'subject' => 'x',
        'headers' => [], 'created_at' => now(),
    ]);

    expect((new EmailReceived($email))->broadcastOn())->toBe([]);
});

it('does not broadcast when realtime.enabled=false even if mode=broadcast', function () {
    config()->set('mailulator.receiver.realtime.enabled', false);
    config()->set('mailulator.receiver.realtime.mode', 'broadcast');

    $email = $this->inbox->emails()->create([
        'from' => 'a@b.com', 'to' => ['c@d.com'], 'subject' => 'x',
        'headers' => [], 'created_at' => now(),
    ]);

    expect((new EmailReceived($email))->broadcastOn())->toBe([]);
});
