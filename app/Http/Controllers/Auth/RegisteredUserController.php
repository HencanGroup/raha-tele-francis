<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // Use transaction to ensure all operations succeed or fail together
        $user = DB::transaction(function () use ($request) {
            // Create user using validated data from the request
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'credits' => 20.00, // Welcome bonus credits
                'total_credits_earned' => 20.00, // Track earned credits
                'status' => 'active',
                'phone_verified' => false,
            ]);

            // Assign default role (member)
            $role = Role::firstOrCreate(['name' => 'member']);
            $user->assignRole($role);

            // // Create user profile
            // $user->profile()->create([
            //     // Add any default profile fields here if needed
            // ]);

            // Log credit transaction for the welcome bonus
            $user->creditTransactions()->create([
                'type' => 'welcome',
                'amount' => 20.00,
                'balance_before' => 0.00,
                'balance_after' => 20.00,
                'description' => 'Welcome bonus credits',
            ]);

            // Fire registered event (this can trigger email verification)
            event(new Registered($user));

            return $user;
        });

        // Log the user in
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}