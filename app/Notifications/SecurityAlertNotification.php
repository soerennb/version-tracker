<?php

namespace App\Notifications;

use App\Models\Vulnerability;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public Vulnerability $vulnerability)
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
        $version = $this->vulnerability->affectedVersion;
        $software = $version?->software;

        return (new MailMessage)
            ->subject(__('notifications.security_alert.subject', ['cve' => $this->vulnerability->cve_id]))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name ?? __('notifications.team')]))
            ->line(__('notifications.security_alert.body', [
                'cve' => $this->vulnerability->cve_id,
                'severity' => $this->vulnerability->getSeverityLabelAttribute(),
                'software' => $software?->name ?? 'n/a',
                'version' => $version?->version_number ?? 'n/a',
            ]))
            ->line($this->vulnerability->description)
            ->action(__('notifications.view_vulnerability'), url('/admin'))
            ->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vulnerability_id' => $this->vulnerability->id,
            'cve_id' => $this->vulnerability->cve_id,
            'severity' => $this->vulnerability->severity?->value,
        ];
    }
}
