<?php

use Illuminate\Support\Facades\Route;
use Webteractive\Mailulator\Http\Controllers\AttachmentDownloadController;
use Webteractive\Mailulator\Http\Controllers\EmailController;
use Webteractive\Mailulator\Http\Controllers\EmailPreviewController;
use Webteractive\Mailulator\Http\Controllers\HomeController;
use Webteractive\Mailulator\Http\Controllers\InboxController;
use Webteractive\Mailulator\Http\Middleware\Authorize;

Route::prefix('api')->name('mailulator.api.')->group(function () {
    Route::get('/inboxes', [InboxController::class, 'index'])->name('inboxes.index');
    Route::get('/inboxes/{inbox}/emails', [EmailController::class, 'index'])->name('inboxes.emails.index');
    Route::post('/inboxes/{inbox}/mark-read', [EmailController::class, 'markAllRead'])->name('inboxes.mark-read');
    Route::delete('/inboxes/{inbox}/emails', [EmailController::class, 'deleteAll'])->name('inboxes.emails.delete-all');
    Route::get('/emails/{email}', [EmailController::class, 'show'])->name('emails.show');
    Route::post('/emails/{email}/read', [EmailController::class, 'read'])->name('emails.read');
    Route::delete('/emails/{email}', [EmailController::class, 'destroy'])->name('emails.destroy');

    Route::middleware(Authorize::class)->group(function () {
        Route::post('/inboxes', [InboxController::class, 'store'])->name('inboxes.store');
        Route::patch('/inboxes/{inbox}', [InboxController::class, 'update'])->name('inboxes.update');
        Route::delete('/inboxes/{inbox}', [InboxController::class, 'destroy'])->name('inboxes.destroy');
        Route::post('/inboxes/{inbox}/regenerate-key', [InboxController::class, 'regenerateKey'])->name('inboxes.regenerate');
    });
});

Route::get('/emails/{email}/preview', EmailPreviewController::class)->name('mailulator.emails.preview');
Route::get('/emails/{email}/attachments/{attachment}', AttachmentDownloadController::class)->name('mailulator.attachments.download');

Route::get('/{any?}', [HomeController::class, 'index'])
    ->where('any', '.*')
    ->name('mailulator.spa');
