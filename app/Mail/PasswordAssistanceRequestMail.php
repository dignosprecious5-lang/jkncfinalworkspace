<?php

namespace App\Mail;

use App\Models\PasswordAssistanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordAssistanceRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PasswordAssistanceRequest $assistanceRequest) {}

    public function build()
    {
        return $this->subject('Password Assistance Request Submitted')
            ->view('emails.users.password-assistance-request')
            ->with([
                'assistanceRequest' => $this->assistanceRequest,
            ]);
    }
}
