<?php

namespace App\Modules\Inbox\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the merchant's team a customer replied. No customer data in the mail.
 */
class CustomerNeedsHuman extends Notification
{
    use Queueable;

    public function __construct(public readonly string $reason) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('عميل رد على واتساب ومحتاج حد من الفريق')
            ->line('فيه عميل رد على رسالة من متجرك، وإرسال الرسائل الآلية ليه اتوقف.')
            ->line('افتح صندوق المحادثات في CODEX Commerce ورد عليه من تطبيق واتساب للأعمال.');
    }
}
