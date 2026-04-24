<?php

use Illuminate\Support\Facades\Broadcast;
use Webteractive\Mailulator\Mailulator;

Broadcast::channel('mailulator.inbox.{inboxId}', function ($user, $inboxId) {
    return Mailulator::userCanViewInbox($user, (int) $inboxId);
});
