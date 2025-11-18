<?php

namespace App\Notifications;

use App\Models\Version;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VersionApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Version $version)
    {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.version_approved.subject', ['version' => $this->version->version_number]))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name ?? __('notifications.team')]))
            ->line(__('notifications.version_approved.body', [
                'software' => $this->version->software?->name ?? 'n/a',
                'version' => $this->version->version_number,
            ]))
            ->action(__('notifications.view_version'), url('/admin'))
            ->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'software_id' => $this->version->software_id,
            'version_id' => $this->version->id,
            'version_number' => $this->version->version_number,
        ];
    }
}
