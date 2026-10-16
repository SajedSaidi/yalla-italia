<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends a mailable immediately. A mail server failure is reported (logged)
 * instead of thrown, so it never breaks the page or command that triggered it.
 */
class SafeMail
{
    public static function send(string|array $to, Mailable $mailable): bool
    {
        try {
            Mail::to($to)->send($mailable);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
