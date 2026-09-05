<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     */
    public function __construct(public string $token)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('คำขอรีเซ็ตรหัสผ่านบัญชี — PDFkrub')
            ->greeting('สวัสดีครับ/ค่ะ,')
            ->line('เราได้รับคำขอสำหรับรีเซ็ตรหัสผ่านบัญชี PDFkrub ของคุณ')
            ->action('ตั้งรหัสผ่านใหม่', $url)
            ->line('ลิงก์สำหรับตั้งรหัสผ่านใหม่นี้จะหมดอายุภายใน 60 นาที')
            ->line('หากคุณไม่ได้เป็นผู้ส่งคำขอนี้ สามารถเพิกเฉยต่ออีเมลฉบับนี้ได้ บัญชีของคุณยังคงปลอดภัย')
            ->salutation('ขอแสดงความนับถือ' . "\n" . 'ทีมงาน PDFkrub');
    }
}
