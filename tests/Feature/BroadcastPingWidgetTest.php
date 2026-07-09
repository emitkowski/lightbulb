<?php

namespace Tests\Feature;

use App\Filament\Widgets\BroadcastPingWidget;
use App\Jobs\BroadcastPingJob;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class BroadcastPingWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_widget_renders_with_the_ping_button_and_echo_script(): void
    {
        Livewire::test(BroadcastPingWidget::class)
            ->assertOk()
            ->assertSee('WebSocket Ping')
            ->assertSeeHtml('wire:click="ping"')
            ->assertSeeHtml('broadcast-ping.');
    }

    public function test_ping_action_dispatches_the_broadcast_job_and_sets_waiting_status(): void
    {
        Queue::fake();

        Livewire::test(BroadcastPingWidget::class)
            ->call('ping')
            ->assertSet('status', 'waiting');

        Queue::assertPushed(BroadcastPingJob::class);
    }

    public function test_on_ping_marks_the_widget_as_received(): void
    {
        Livewire::test(BroadcastPingWidget::class)
            ->call('onPing')
            ->assertSet('status', 'received')
            ->assertSet('receivedAt', fn ($value) => $value !== null);
    }

    public function test_mount_populates_the_current_user_id(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BroadcastPingWidget::class)
            ->assertSet('userId', (string) $user->id);
    }
}
