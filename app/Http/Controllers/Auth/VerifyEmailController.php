<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark a user's email address as verified via a signed magic link.
     *
     * The link authenticates the user itself (the URL signature is the proof),
     * so this route lives outside the `auth` middleware group. That lets a
     * logged-out user click their welcome email and still be logged in and
     * routed to the destination for their user_type.
     */
    public function __invoke(string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        // The `signed` middleware already validated the URL signature; this
        // guards against a valid signature being reused for another user/email.
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        // Log the user in so the downstream flow (temp-password reset, panel
        // access) has an authenticated session to work with.
        if (! Auth::check() || Auth::id() !== $user->getKey()) {
            Auth::login($user);
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->to($this->redirectFor($user).'?verified=1');
    }

    /**
     * Resolve the post-verification landing path for the given user.
     */
    private function redirectFor(User $user): string
    {
        if ($user->isSystemUser()) {
            return '/admin-panel';
        }

        return route('dashboard', absolute: false);
    }
}
