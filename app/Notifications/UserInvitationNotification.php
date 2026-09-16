<?php

namespace App\Notifications;

use App\Models\UserInvitation;
use App\Services\RuntimeSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public UserInvitation $invitation,
        #[\SensitiveParameter] public string $token,
    ) {
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
            ->subject(__('notifications.invitation.subject'))
            ->greeting(__('notifications.greeting', ['name' => $this->invitation->name ?? __('notifications.team')]))
            ->line(__('notifications.invitation.body', [
                'app' => app(RuntimeSettings::class)->general()->application_name,
            ]))
            ->action(__('notifications.invitation.action'), url('/account/invitation?token='.urlencode($this->token)))
            ->line(__('notifications.invitation.expiry', ['date' => $this->invitation->expires_at?->toDateString()]))
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
            'type' => 'user_invitation',
            'invitation_id' => $this->invitation->id,
        ];
    }
}
