<?php

namespace Tests\Feature;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * A User subclass that implements MustVerifyEmail so the
 * updateVerifiedUser branch in UpdateUserProfileInformation can be exercised.
 */
class VerifiableUser extends User implements MustVerifyEmail
{
    protected $table = 'users';

    public function sendEmailVerificationNotification(): void
    {
        // no-op in tests
    }
}

class UpdateUserProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_saves_name_and_email(): void
    {
        $user   = User::factory()->create(['name' => 'Old', 'email' => 'old@example.com']);
        $action = new UpdateUserProfileInformation();

        $action->update($user, ['name' => 'New Name', 'email' => 'new@example.com']);

        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('new@example.com', $user->fresh()->email);
    }

    public function test_update_same_email_takes_else_branch(): void
    {
        $user   = User::factory()->create(['email' => 'same@example.com']);
        $action = new UpdateUserProfileInformation();

        // Same email — just save, no verification path
        $action->update($user, ['name' => 'No Change Email', 'email' => 'same@example.com']);

        $this->assertSame('No Change Email', $user->fresh()->name);
    }

    public function test_update_with_photo_calls_update_profile_photo(): void
    {
        $user  = User::factory()->create();
        $photo = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        // Mock the user so updateProfilePhoto() doesn't actually write to disk
        $mockUser = \Mockery::mock($user)->makePartial();
        $mockUser->shouldReceive('updateProfilePhoto')->once()->with($photo);
        $mockUser->shouldReceive('forceFill')->passthru();
        $mockUser->shouldReceive('save')->passthru();

        $action = new UpdateUserProfileInformation();
        $action->update($mockUser, [
            'name'  => $mockUser->name,
            'email' => $mockUser->email,
            'photo' => $photo,
        ]);

        $this->assertTrue(true); // expectation is the Mockery assertion above
    }

    public function test_update_triggers_verified_user_path_when_email_changes(): void
    {
        // Create a real DB row
        $dbUser = User::factory()->create([
            'email'             => 'original@example.com',
            'email_verified_at' => now(),
        ]);

        // Load it as a VerifiableUser (which implements MustVerifyEmail)
        $verifyingUser = VerifiableUser::find($dbUser->id);

        $action = new UpdateUserProfileInformation();
        $action->update($verifyingUser, [
            'name'  => 'Changed',
            'email' => 'changed@example.com',
        ]);

        // updateVerifiedUser nullifies email_verified_at and updates fields
        $fresh = $dbUser->fresh();
        $this->assertNull($fresh->email_verified_at);
        $this->assertSame('Changed', $fresh->name);
        $this->assertSame('changed@example.com', $fresh->email);
    }
}
