<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\Favorite;
use App\Models\Member;
use App\Models\Message;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Render dashboard based on user role.
     */
    public function index()
    {
        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        return match (true) {
            // System staff (super_admin/admin/manager/...) live in the Filament
            // panel — never render the Inertia dashboard for them.
            $user->isSystemUser() => redirect('/admin-panel'),
            // Discriminate by user_type (not Spatie roles): seeded escorts and
            // members have no role rows yet, so hasRole() would 403 them.
            $user->isEscort() => $this->renderEscortDashboard($user),
            $user->isMember() => $this->renderMemberDashboard($user),
            default => abort(403),
        };
    }

    /* ==========================================================
     | ADMIN DASHBOARD
     |========================================================== */

    private function renderAdminDashboard()
    {
        return Inertia::render('Backend/Dashboard/Admin', [
            'dashboardData' => $this->getAdminData(),
        ]);
    }

    private function getAdminData(): array
    {
        return [
            'stats' => [
                $this->card('👥 Total Users', User::count(), 'primary', 'All registered users'),
                $this->card('💃 Escorts', User::where('user_type', 'escort')->count(), 'danger', 'Total escorts'),
                $this->card('🧑‍💼 Members', User::where('user_type', 'member')->count(), 'info', 'Total members'),
                $this->card(
                    '💰 Credits Issued',
                    Member::sum('total_credits_earned'),
                    'success',
                    'Total credits ever issued'
                ),
            ],
        ];
    }

    /* ==========================================================
     | ESCORT DASHBOARD
     |========================================================== */

    private function renderEscortDashboard(User $user)
    {
        return Inertia::render('Backend/Dashboard/Escort', [
            'dashboardData' => $this->getEscortData($user),
        ]);
    }

    private function getEscortData(User $user): array
    {
        return [
            'stats' => [
                $this->card(
                    '📩 Messages',
                    Message::where('receiver_id', $user->id)->count(),
                    'info',
                    'Messages received'
                ),
                $this->card(
                    '⭐ Reviews',
                    Review::where('escort_id', $user->id)->count(),
                    'warning',
                    'Client reviews'
                ),
                $this->card(
                    '❤️ Favorites',
                    Favorite::where('escort_id', $user->id)->count(),
                    'danger',
                    'Users who favorited you'
                ),
                $this->card(
                    '💰 Credits Earned',
                    number_format($user->escortProfile?->earnings ?? 0, 2),
                    'success',
                    'Total credits earned'
                ),
            ],
        ];
    }

    /* ==========================================================
     | MEMBER DASHBOARD
     |========================================================== */

    private function renderMemberDashboard(User $user)
    {
        return Inertia::render('Backend/Dashboard/Member', [
            'dashboardData' => $this->getMemberData($user),
        ]);
    }

    private function getMemberData(User $user): array
    {
        $member = $user->memberProfile;

        return [
            'stats' => [
                $this->card(
                    '💰 Credit Balance',
                    number_format($member ? $member->credits : 0, 2),
                    'success',
                    'Available credits'
                ),
                $this->card(
                    '📩 Messages',
                    Message::where('receiver_id', $user->id)->count(),
                    'info',
                    'Messages received'
                ),
                $this->card(
                    '❤️ Favorites',
                    Favorite::where('user_id', $user->id)->count(),
                    'danger',
                    'Saved escorts'
                ),
                $this->card(
                    '🧾 Transactions',
                    CreditTransaction::where('user_id', $user->id)->count(),
                    'secondary',
                    'Credit history'
                ),
            ],
            'conversations' => $user->conversations()
                ->latest('last_message_at')
                ->get(),
        ];
    }

    /* ==========================================================
     | REUSABLE CARD BUILDER
     |========================================================== */

    private function card(
        string $title,
        mixed $value,
        string $color,
        string $description
    ): array {
        return [
            'title' => $title,
            'value' => $value,
            'icon' => strtok($title, ' '),
            'trend' => null,
            'trendDirection' => 'neutral',
            'link' => '#',
            'color' => $color,
            'description' => $description,
        ];
    }
}
