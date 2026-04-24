<?php

namespace Webteractive\Mailulator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webteractive\Mailulator\Mailulator;
use Webteractive\Mailulator\Models\Email;

class EmailPreviewController extends Controller
{
    public function __invoke(Request $request, Email $email): Response
    {
        if (! Mailulator::userCanViewInbox($request->user(), $email->inbox_id)) {
            throw new NotFoundHttpException;
        }

        $html = $email->html_body ?? '<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;color:#666;padding:2rem;">(no HTML body)</body>';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; img-src data: https:; style-src 'unsafe-inline'",
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
