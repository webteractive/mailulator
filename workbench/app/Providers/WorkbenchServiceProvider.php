<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Inbox;

/**
 * Dev-only convenience provider. Opens the Mailulator gates so the SPA is
 * reachable without a logged-in user, and seeds a predictable default inbox
 * with a fixed plaintext token so sender apps can point at it immediately.
 *
 * Token: mailulator-local-dev-token
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mailulator::auth(fn () => true);
        Mailulator::canViewInbox(fn () => true);
        Mailulator::manage(fn () => true);

        $this->app->booted(function () {
            if (! Schema::connection('mailulator')->hasTable('inboxes')) {
                return;
            }

            if (Inbox::query()->exists()) {
                return;
            }

            Inbox::query()->forceCreate([
                'name' => 'Default',
                'api_key' => Inbox::hashToken('mailulator-local-dev-token'),
                'is_default' => true,
            ]);
        });
    }
}
