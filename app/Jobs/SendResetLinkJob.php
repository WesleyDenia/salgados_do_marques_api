<?php

namespace App\Jobs;

use App\Mail\ResetPasswordMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendResetLinkJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $email, private readonly string $token)
    {
    }

    public function handle(): void
    {
        $baseUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $resetUrl = sprintf(
            '%s/auth/reset-password?token=%s',
            $baseUrl,
            urlencode($this->token)
        );

        try {
            Mail::to($this->email)->send(new ResetPasswordMail($resetUrl));

        } catch (\Throwable $exception) {
       

            throw $exception;
        }
    }
}
