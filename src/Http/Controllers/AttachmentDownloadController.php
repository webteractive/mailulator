<?php

namespace Webteractive\Mailulator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Attachment;
use Webteractive\Mailulator\Models\Email;

class AttachmentDownloadController extends Controller
{
    public function __invoke(Request $request, Email $email, Attachment $attachment): StreamedResponse
    {
        if ($attachment->email_id !== $email->id) {
            throw new NotFoundHttpException;
        }

        if (! Mailulator::userCanViewInbox($request->user(), $email->inbox_id)) {
            throw new NotFoundHttpException;
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->filename,
            ['Content-Type' => $attachment->mime_type]
        );
    }
}
