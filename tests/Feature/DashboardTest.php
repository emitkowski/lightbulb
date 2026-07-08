<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/admin');
    }

    public function test_inertia_visit_to_dashboard_gets_a_location_response_instead_of_a_modal(): void
    {
        $user = User::factory()->create();

        $version = hash_file('xxh128', public_path('build/manifest.json'));

        $response = $this->actingAs($user)
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])
            ->get('/dashboard');

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', '/admin');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_visiting_admin_panel_directly_is_redirected_to_the_app_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }
}
