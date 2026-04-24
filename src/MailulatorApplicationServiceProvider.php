<?php

namespace Webteractive\Mailulator;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

abstract class MailulatorApplicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->authorization();
    }

    protected function authorization(): void
    {
        $this->gate();

        Mailulator::auth(function ($request) {
            return Gate::check('viewMailulator', [$request->user()])
                || app()->environment('local');
        });
    }

    /**
     * Override to define who can access Mailulator in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewMailulator', function ($user) {
            return in_array(optional($user)->email, [
                //
            ]);
        });
    }

    public function register(): void
    {
        //
    }
}
