<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('profile.jpg', 600, 600),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertStringStartsWith('profile-photos/', $user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_uploading_new_profile_photo_removes_previous_file(): void
    {
        Storage::fake('public');
        $oldAvatarPath = 'profile-photos/old-profile.jpg';
        Storage::disk('public')->put($oldAvatarPath, 'old photo');
        $user = User::factory()->create(['avatar_path' => $oldAvatarPath]);

        $this->actingAs($user)
            ->post('/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('replacement.png', 500, 500),
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertNotSame($oldAvatarPath, $user->avatar_path);
        Storage::disk('public')->assertMissing($oldAvatarPath);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_profile_photo_rejects_unsupported_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', [
                'avatar' => UploadedFile::fake()->create('profile.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors(['avatar']);

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_profile_photo_rejects_files_larger_than_two_megabytes(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('large-profile.jpg')->size(2049),
            ])
            ->assertSessionHasErrors(['avatar']);

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_guest_cannot_upload_profile_photo(): void
    {
        Storage::fake('public');

        $this->post('/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('profile.jpg'),
        ])->assertRedirect('/login');
    }
}
