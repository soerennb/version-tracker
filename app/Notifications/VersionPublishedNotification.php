<?php

namespace App\Notifications;

use App\Models\Version;
use App\Notifications\Concerns\HasNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VersionPublishedNotification extends Notification implements ShouldQueue
{
    use HasNotificationDelivery;
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
        return $this->deliveryChannels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.version_published.subject', ['version' => $this->version->version_number]))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name ?? __('notifications.team')]))
            ->line(__('notifications.version_published.body', [
                'software' => $this->version->software?->name ?? 'n/a',
                'version' => $this->version->version_number,
            ]))
            ->action(__('notifications.view_version'), url('/releases/'.$this->version->id))
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
            'type' => 'version_published',
            'title' => __('notifications.version_published.subject', ['version' => $this->version->version_number]),
            'message' => __('notifications.version_published.body', [
                'software' => $this->version->software?->name ?? 'n/a',
                'version' => $this->version->version_number,
            ]),
            'action_url' => url('/releases/'.$this->version->id),
            'software_id' => $this->version->software_id,
            'version_id' => $this->version->id,
            'version_number' => $this->version->version_number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
