<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $fullName;

    public function __construct(string $otp, string $fullName = '')
    {
        $this->otp      = $otp;
        $this->fullName = $fullName;
    }

    public function build(): static
    {
        $name = $this->fullName ?: 'عزيزنا المستخدم';

        return $this->subject('رمز التحقق - Edu Bridge')
                    ->html("
                        <div style='direction:rtl;font-family:Tahoma,Arial,sans-serif;max-width:480px;margin:auto;
                                    border:1px solid #e8e8e8;border-radius:12px;padding:30px;text-align:center;'>
                            <h2 style='color:#1a1a1a;'>Edu Bridge 🎓</h2>
                            <p style='font-size:16px;'>مرحباً <strong>{$name}</strong>،</p>
                            <p>شكراً لتسجيلك. رمز التحقق الخاص بك هو:</p>
                            <div style='background:#f6f6f6;border-radius:8px;padding:18px;margin:20px 0;
                                        font-size:36px;font-weight:bold;letter-spacing:10px;color:#222;'>
                                {$this->otp}
                            </div>
                            <p style='color:#888;font-size:13px;'>هذا الرمز صالح لمدة <strong>15 دقيقة</strong> فقط.<br>
                               إذا لم تطلب هذا الرمز تجاهل هذا البريد.</p>
                        </div>
                    ");
    }
}
