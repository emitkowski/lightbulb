<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginIntendedUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_login_after_being_bounced_from_admin_gets_a_location_response_instead_of_a_modal(): void
    {
        $user = User::factory()->create();

        $this->get('/admin');

        $version = hash_file('xxh128', public_path('build/manifest.json'));

        $response = $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', url('/admin'));
    }

    public function test_non_inertia_login_after_being_bounced_from_admin_still_gets_a_plain_redirect(): void
    {
        $user = User::factory()->create();

        $this->get('/admin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
    }
}
