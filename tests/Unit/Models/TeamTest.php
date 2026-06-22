<?php

namespace Tests\Unit\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_is_assigned_a_uuid_on_creation(): void
    {
        $owner = User::factory()->create();
        $team  = Team::create(['owner_id' => $owner->id, 'name' => 'Test Team']);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $team->id
        );
    }

    public function test_owner_relationship_returns_correct_user(): void
    {
        $owner = User::factory()->create();
        $team  = Team::create(['owner_id' => $owner->id, 'name' => 'Test Team']);

        $this->assertTrue($team->owner->is($owner));
    }

    public function test_has_member_returns_true_for_team_member(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $team   = Team::create(['owner_id' => $owner->id, 'name' => 'Test Team']);
        $team->members()->attach($member->id);

        $this->assertTrue($team->hasMember($member));
    }

    public function test_has_member_returns_false_for_non_member(): void
    {
        $owner    = User::factory()->create();
        $outsider = User::factory()->create();
        $team     = Team::create(['owner_id' => $owner->id, 'name' => 'Test Team']);

        $this->assertFalse($team->hasMember($outsider));
    }
}
