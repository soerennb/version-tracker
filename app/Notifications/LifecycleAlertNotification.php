<?php

namespace App\Notifications;

use App\Models\Version;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LifecycleAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public Version $version)
    {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.lifecycle_alert.subject', [
                'software' => $this->version->software?->name ?? 'n/a',
                'version' => $this->version->version_number,
            ]))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name ?? __('notifications.team')]))
            ->line(__('notifications.lifecycle_alert.body', [
                'software' => $this->version->software?->name ?? 'n/a',
                'version' => $this->version->version_number,
                'date' => $this->version->eol_date?->format('Y-m-d') ?? 'n/a',
            ]))
            ->action(__('notifications.view_version'), url('/admin'))
            ->line(__('notifications.thanks'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'version_id' => $this->version->id,
            'software_id' => $this->version->software_id,
            'eol_date' => $this->version->eol_date?->toDateString(),
        ];
    }
}
