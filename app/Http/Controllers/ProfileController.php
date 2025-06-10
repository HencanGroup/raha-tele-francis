<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function show(string $id): Response
    {
        $user = User::find($id);

        return Inertia::render('Backend/Profile/Show', [
            'user' => $user
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Backend/Profile/Edit');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request)
    {

        try {
            $user = $request->user();
            $validated = $request->validated();

            // Handle email verification status
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            // Handle file uploads
            if ($request->hasFile('profile_picture')) {
                $validated['profile_picture'] = $request->file('profile_picture')
                    ->store('profile-pictures', 'public');
            }

            if ($request->hasFile('gallery')) {
                $validated['gallery'] = array_map(
                    fn($file) => $file->store('gallery', 'public'),
                    $request->file('gallery')
                );
            }

            if ($request->hasFile('verification_documents')) {
                $validated['verification_documents'] = array_map(
                    fn($file) => $file->store('verification-documents', 'public'),
                    $request->file('verification_documents')
                );
            }

            $user->fill($validated)->save();

            return response()->json([
                'success' => 'Profile updated successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Profile update failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('status', 'Account deleted successfully.');
    }
}
