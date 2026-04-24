<?php

namespace Webteractive\Mailulator\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webteractive\Mailulator\Mailulator;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $realtime = config('mailulator.receiver.realtime');

        /** @var Factory $factory */
        $factory = app('view');

        return $factory->make('mailulator::app', [
            'config' => [
                'apiBase' => url(config('mailulator.receiver.ui.path', 'mailulator').'/api'),
                'basePath' => '/'.trim(config('mailulator.receiver.ui.path', 'mailulator'), '/'),
                'realtime' => [
                    'enabled' => (bool) ($realtime['enabled'] ?? true),
                    'mode' => $realtime['mode'] ?? 'polling',
                    'pollInterval' => (int) ($realtime['poll_interval'] ?? 3),
                    'broadcaster' => $realtime['broadcaster'] ?? 'reverb',
                    'echo' => $realtime['echo'] ?? [],
                ],
                'userId' => optional($request->user())->getAuthIdentifier(),
                'isAdmin' => Mailulator::userCanManage($request->user()),
            ],
        ]);
    }
}
