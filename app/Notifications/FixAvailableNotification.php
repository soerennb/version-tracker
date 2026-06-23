<?php

namespace App\Notifications;

use App\Models\Vulnerability;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FixAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(public Vulnerability $vulnerability)
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
            ->subject(__('notifications.fix_available.subject', [
                'cve' => $this->vulnerability->cve_id,
            ]))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name ?? __('notifications.team')]))
            ->line(__('notifications.fix_available.body', [
                'cve' => $this->vulnerability->cve_id,
                'software' => $this->vulnerability->affectedVersion?->software?->name ?? 'n/a',
                'version' => $this->vulnerability->fixedVersion?->version_number ?? 'n/a',
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
            'vulnerability_id' => $this->vulnerability->id,
            'cve_id' => $this->vulnerability->cve_id,
            'fixed_version_id' => $this->vulnerability->fixed_version_id,
        ];
    }
}
