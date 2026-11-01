<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * オンラインサインアップの受付を通知するメール
 */
class SignupReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * サインアップしたユーザー
     *
     * @var User
     */
    public $user;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('【災図】利用登録のお申し込みを受け付けました')
            ->view('emails.signup_received_mail');
    }
}
