<?php

namespace App\Notifications;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly TeamInvitation $invitation) {}

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
        $teamName    = $this->invitation->team->name;
        $inviterName = $this->invitation->inviter?->name ?? 'A team member';
        $acceptUrl   = route('team.invitation', $this->invitation->token);

        return (new MailMessage)
            ->subject("{$inviterName} invited you to join {$teamName}")
            ->greeting("You've been invited!")
            ->line("{$inviterName} has invited you to join their team **{$teamName}** on " . config('app.name') . '.')
            ->action('Accept Invitation', $acceptUrl)
            ->line('This invitation expires in 7 days.')
            ->line('If you did not expect this invitation, you can ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
