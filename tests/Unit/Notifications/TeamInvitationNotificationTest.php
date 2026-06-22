<?php

namespace Tests\Unit\Notifications;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class TeamInvitationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private TeamInvitation $invitation;
    private User $inviter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inviter    = User::factory()->create(['name' => 'Alice']);
        $team             = Team::create(['owner_id' => $this->inviter->id, 'name' => 'Acme Corp']);
        $this->invitation = TeamInvitation::generate($team, 'bob@example.com', $this->inviter->id);
    }

    // --- via() ---

    public function test_notification_is_sent_via_mail(): void
    {
        $notification = new TeamInvitationNotification($this->invitation);
        $notifiable   = User::factory()->make();

        $channels = $notification->via($notifiable);

        $this->assertSame(['mail'], $channels);
    }

    // --- toMail() ---

    public function test_to_mail_returns_mail_message(): void
    {
        $notification = new TeamInvitationNotification($this->invitation);
        $notifiable   = User::factory()->make();

        $mail = $notification->toMail($notifiable);

        $this->assertInstanceOf(MailMessage::class, $mail);
    }

    public function test_to_mail_subject_contains_inviter_name_and_team_name(): void
    {
        $notification = new TeamInvitationNotification($this->invitation);
        $notifiable   = User::factory()->make();

        $mail = $notification->toMail($notifiable);

        $this->assertStringContainsString('Alice', $mail->subject);
        $this->assertStringContainsString('Acme Corp', $mail->subject);
    }

    public function test_to_mail_contains_accept_action(): void
    {
        $notification = new TeamInvitationNotification($this->invitation);
        $notifiable   = User::factory()->make();

        $mail = $notification->toMail($notifiable);

        // The action URL and label should be present
        $this->assertNotEmpty($mail->actionText);
        $this->assertNotEmpty($mail->actionUrl);
    }

    // --- toArray() ---

    public function test_to_array_returns_empty_array(): void
    {
        $notification = new TeamInvitationNotification($this->invitation);
        $notifiable   = User::factory()->make();

        $result = $notification->toArray($notifiable);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
