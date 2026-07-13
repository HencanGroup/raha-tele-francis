<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SystemUserVerifyRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function signedUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
    }

    /**
     * A brand-new system user (temp password, unverified) who clicks the
     * welcome-email link while logged out must still be authenticated and
     * landed on the admin panel — the must-reset-password flow takes over there.
     */
    public function test_logged_out_new_system_user_reaches_admin_panel(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create([
            'user_type' => 'system_user',
            'password' => Hash::make('temp-pass'),
            'is_temp_password' => true,
        ]);

        $response = $this->get($this->signedUrl($user));

        $response->assertRedirect('/admin-panel?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertTrue(auth()->check(), 'the signed link should authenticate the user');
        Event::assertDispatched(Verified::class);
    }

    public function test_member_reaches_dashboard(): void
    {
        $user = User::factory()->unverified()->create(['user_type' => 'member']);

        $this->get($this->signedUrl($user))
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_wrong_hash_is_rejected(): void
    {
        $user = User::factory()->unverified()->create(['user_type' => 'system_user']);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
