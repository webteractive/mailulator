<?php

namespace Webteractive\Mailulator;

use Closure;
use Illuminate\Http\Request;

class Mailulator
{
    /**
     * The callback that decides whether the incoming request may access Mailulator at all.
     *
     * @var Closure|null
     */
    public static $authUsing;

    /**
     * The callback that decides whether a given user may view a given inbox.
     *
     * @var Closure|null
     */
    public static $inboxVisibilityUsing;

    /**
     * The callback that decides whether the given user may manage Mailulator (admin).
     *
     * @var Closure|null
     */
    public static $manageUsing;

    /**
     * Register the callback that authorizes Mailulator access in non-local environments.
     */
    public static function auth(Closure $callback): void
    {
        static::$authUsing = $callback;
    }

    /**
     * Determine whether the given request may access Mailulator.
     */
    public static function check(Request $request): bool
    {
        $callback = static::$authUsing ?? fn () => app()->environment('local');

        return (bool) $callback($request);
    }

    /**
     * Register the callback that filters inbox visibility per user.
     */
    public static function canViewInbox(Closure $callback): void
    {
        static::$inboxVisibilityUsing = $callback;
    }

    /**
     * Determine whether the given user may view the given inbox id.
     */
    public static function userCanViewInbox(mixed $user, int|string $inboxId): bool
    {
        $callback = static::$inboxVisibilityUsing ?? fn ($user, $inboxId) => true;

        return (bool) $callback($user, $inboxId);
    }

    /**
     * Register the callback that decides who can manage Mailulator.
     */
    public static function manage(Closure $callback): void
    {
        static::$manageUsing = $callback;
    }

    /**
     * Determine whether the given user may manage Mailulator.
     */
    public static function userCanManage(mixed $user): bool
    {
        $callback = static::$manageUsing ?? fn ($user) => (bool) ($user->is_admin ?? false);

        return (bool) $callback($user);
    }
}
