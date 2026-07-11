<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (Exception) {
            return response()->json(['message' => 'Authentication failed.'], 401);
        }

        $user = DB::transaction(function () use ($socialUser, $provider) {
            $existing = User::where('email', $socialUser->getEmail())->first();

            if ($existing) {
                if ($existing->memberProfile) {
                    $existing->memberProfile->update([
                        'social_id' => $socialUser->getId(),
                        'social_provider' => $provider,
                        'social_avatar' => $socialUser->getAvatar(),
                    ]);
                }

                return $existing;
            }

            $name = $socialUser->getName() ?? $socialUser->getNickname() ?? 'User';
            $nameParts = explode(' ', $name, 2);

            $user = User::create([
                'name' => $name,
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::password(16)),
                'email_verified_at' => now(),
                'profile_picture' => $socialUser->getAvatar(),
                'user_type' => 'member',
                'status' => 'active',
            ]);

            $role = Role::firstOrCreate(['name' => 'member']);
            $user->assignRole($role);

            Member::create([
                'user_id' => $user->id,
                'social_id' => $socialUser->getId(),
                'social_provider' => $provider,
                'social_avatar' => $socialUser->getAvatar(),
                'credits' => 20.00,
                'total_credits_earned' => 20.00,
            ]);

            $user->creditTransactions()->create([
                'type' => 'welcome',
                'amount' => 20.00,
                'balance_before' => 0.00,
                'balance_after' => 20.00,
                'description' => 'Welcome bonus credits (social login)',
            ]);

            return $user;
        });

        $token = $user->createToken('social-auth')->plainTextToken;

        return redirect(config('services.socialite.redirect_frontend').'?token='.$token);
    }

    private function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'facebook'], true)) {
            abort(404, 'Unsupported provider.');
        }
    }
}
