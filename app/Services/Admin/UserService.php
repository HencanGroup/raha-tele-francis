<?php

namespace App\Services\Admin;

use App\Mail\Admin\UserMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{
    public function __construct()
    {
        //
    }

    public function onCreate(User $user, ?string $password): void
    {
        Mail::to($user->email)->queue(new UserMail($user, $password));
    }

    public function suspend(User $user): void
    {
        $user->update(['status' => 'suspended']);
    }

    public function activate(User $user): void
    {
        $user->update(['status' => 'active']);
    }

    public function forcePasswordReset(User $user): void
    {
        $password = Str::random(12);

        $user->update([
            'password' => bcrypt($password),
            'is_temp_password' => true,
        ]);

        Mail::to($user->email)->queue(new UserMail($user, $password));
    }
}
