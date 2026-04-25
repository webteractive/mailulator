<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\MailulatorServiceProvider;
use Webteractive\Mailulator\Models\Inbox;

it('routes models and migrations through a custom connection name', function () {
    config()->set('database.connections.staging', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    config()->set('mailulator.receiver.database.connection', 'staging');

    Schema::connection('staging')->create('inboxes', function ($table) {
        $table->bigIncrements('id');
        $table->string('name');
        $table->string('api_key', 64)->unique();
        $table->unsignedInteger('retention_days')->nullable();
        $table->boolean('is_default')->default(false);
        $table->json('settings')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamps();
    });

    $inbox = Inbox::query()->forceCreate([
        'name' => 'Custom',
        'api_key' => Inbox::hashToken(Str::random(40)),
    ]);

    expect(Mailulator::connectionName())->toBe('staging')
        ->and($inbox->getConnectionName())->toBe('staging');

    expect(Schema::connection('staging')->hasTable('inboxes'))->toBeTrue();
});

it('skips auto-registration when the connection name is not the default', function () {
    config()->set('mailulator.receiver.database.connection', 'primary');

    $connections = config('database.connections', []);
    unset($connections['primary']);
    config()->set('database.connections', $connections);

    (new MailulatorServiceProvider(app()))->register();

    expect(config()->has('database.connections.primary'))->toBeFalse();
});
