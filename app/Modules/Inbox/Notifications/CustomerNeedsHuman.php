<?php

namespace App\Modules\Inbox\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the merchant's team a customer replied. No customer data in the mail.
 */
// Queued: a slow mail server must never stall or fail the Meta webhook (review H13).
class CustomerNeedsHuman extends Notification implements ShouldQueue
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
