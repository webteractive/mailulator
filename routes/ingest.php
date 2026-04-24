<?php

use Illuminate\Support\Facades\Route;
use Webteractive\Mailulator\Http\Controllers\StoreEmailController;

Route::post('/emails', StoreEmailController::class)->name('mailulator.emails.store');
