<?php

use Illuminate\Config\Repository;
use Webteractive\Mailulator\MailulatorServiceProvider;

it('receiver.enabled=false skips DB connection registration', function () {
    // Start from a clean config so the provider sees no pre-existing connection.
    $app = clone app();
    $app->instance('config', new Repository([
        'mailulator' => [
            'receiver' => ['enabled' => false],
            'driver' => ['enabled' => false],
        ],
        'database' => ['connections' => []],
    ]));

    $provider = new MailulatorServiceProvider($app);
    $provider->register();

    expect($app['config']->has('database.connections.mailulator'))->toBeFalse();
});
