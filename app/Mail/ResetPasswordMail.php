<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $resetUrl)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Redefinição de senha - Salgados do Marquês')
            ->markdown('emails.reset-password', [
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
