<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function show(string $id): Response
    {
        $user = User::findOrFail($id);

        return Inertia::render('Backend/Profile/Show', [
            'user' => $user
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Backend/Profile/Edit', [
            'user' => $request->user()
        ]);
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

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                // Delete old profile picture if exists
                if ($user->profile_picture) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
                $validated['profile_picture'] = $request->file('profile_picture')
                    ->store('profile-pictures', 'public');
            }

            // Handle gallery uploads
            if ($request->hasFile('gallery')) {
                // Delete old gallery images if exists
                if ($user->gallery) {
                    foreach ($user->gallery as $image) {
                        Storage::disk('public')->delete($image);
                    }
                }
                $validated['gallery'] = array_map(
                    fn($file) => $file->store('gallery', 'public'),
                    $request->file('gallery')
                );
            }

            // Handle verification documents upload
            if ($request->hasFile('verification_documents')) {
                // Delete old documents if exists
                if ($user->verification_documents) {
                    foreach ($user->verification_documents as $doc) {
                        Storage::disk('public')->delete($doc);
                    }
                }
                $validated['verification_documents'] = array_map(
                    fn($file) => $file->store('verification-documents', 'public'),
                    $request->file('verification_documents')
                );
            }

            $user->update($validated);

            return response()->json([
                'success' => 'Profile updated successfully.',
                'user' => $user->fresh()
            ]);
        } catch (\Throwable $e) {
            Log::error('Profile update failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update profile. Please try again.',
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
