<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    /**
     * Create a new message instance.
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('رمز التحقق - Edu Bridge')
                    ->html("
                        <div style='direction: rtl; font-family: Tahoma; text-align: center; border: 1px solid #eee; padding: 20px;'>
                            <h2 style='color: #F6E300;'>مرحباً بك في Edu Bridge</h2>
                            <p>شكراً لتسجيلك معنا. رمز التحقق الخاص بك هو:</p>
                            <h1 style='background: #f4f4f4; padding: 10px; display: inline-block; letter-spacing: 5px;'>{$this->otp}</h1>
                            <p>هذا الرمز صالح لمدة 15 دقيقة فقط.</p>
                        </div>
                    ");
    }
}
