<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Inbox;

beforeEach(function () {
    Mailulator::auth(fn () => true);
    Mailulator::canViewInbox(fn () => true);
    Mailulator::manage(fn () => true);
});

it('SPA API routes are on the web middleware group (CSRF enforced in production)', function () {
    $route = Route::getRoutes()->getByName('mailulator.api.emails.read');

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();
    expect($middleware)->toContain('web');
});

it('public ingest routes are on the api middleware group (no CSRF by design)', function () {
    $route = Route::getRoutes()->getByName('mailulator.emails.store');

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();
    expect($middleware)->toContain('api');
    expect($middleware)->not->toContain('web');
});

it('SPA shell emits a csrf-token meta tag for the fetch wrapper fallback', function () {
    $response = $this->get('/mailulator');

    $response->assertOk();
    expect($response->getContent())->toContain('<meta name="csrf-token"');
});

it('ingest endpoint ignores missing CSRF because it is stateless', function () {
    $plaintext = Str::random(40);
    Inbox::query()->forceCreate([
        'name' => 'CSRF',
        'api_key' => Inbox::hashToken($plaintext),
    ]);

    // No cookies, no CSRF token — pure bearer auth.
    $this->postJson('/api/emails', [
        'from' => 'a@b.com',
        'to' => ['c@d.com'],
        'subject' => 'x',
    ], ['Authorization' => 'Bearer '.$plaintext])
        ->assertCreated();
});
