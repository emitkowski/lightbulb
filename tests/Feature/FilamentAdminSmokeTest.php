<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_request_redirects(): void
    {
        $this->get('/admin/ideas')->assertRedirect();
    }

    public function test_ideas_index_renders(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/ideas')
            ->assertOk();
    }

    public function test_ingestion_runs_index_renders(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/ingestion-runs')
            ->assertOk();
    }

    public function test_raw_signals_index_renders(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/raw-signals')
            ->assertOk();
    }

    public function test_raw_signals_create_renders(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/raw-signals/create')
            ->assertOk();
    }
}
