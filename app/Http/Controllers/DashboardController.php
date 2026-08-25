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
        $escortProfile = $user->escortProfile;
        $favoriteCount = Favorite::where('escort_id', $escortProfile?->id ?? 0)->count();

        return [
            'stats' => [
                $this->card(
                    '📩 Messages',
                    Message::where('receiver_id', $user->id)->count(),
                    'info',
                    'Messages received',
                    route('chat.index')
                ),
                $this->card(
                    '⭐ Reviews',
                    Review::where('escort_id', $user->id)->count(),
                    'warning',
                    'Client reviews',
                    $escortProfile ? route('escort.show', $escortProfile->id) : null
                ),
                $this->card(
                    '❤️ Favorites',
                    $favoriteCount,
                    'danger',
                    'Users who favorited you',
                    $favoriteCount > 0 ? route('favorites.index') : null
                ),
                $this->card(
                    '💰 Credits Earned',
                    number_format($escortProfile?->earnings ?? 0, 2),
                    'success',
                    'Total credits earned',
                    route('earnings.index')
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
        $favoriteCount = Favorite::where('user_id', $user->id)->count();

        return [
            'stats' => [
                $this->card(
                    '💰 Credit Balance',
                    number_format($member ? $member->credits : 0, 2),
                    'success',
                    'Available credits',
                    route('transactions.index')
                ),
                $this->card(
                    '📩 Messages',
                    Message::where('receiver_id', $user->id)->count(),
                    'info',
                    'Messages received',
                    route('chat.index')
                ),
                $this->card(
                    '❤️ Favorites',
                    $favoriteCount,
                    'danger',
                    'Saved escorts',
                    $favoriteCount > 0 ? route('favorites.index') : null
                ),
                $this->card(
                    '🧾 Transactions',
                    CreditTransaction::where('user_id', $user->id)->count(),
                    'secondary',
                    'Credit history',
                    route('transactions.index')
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
        string $description,
        ?string $link = null
    ): array {
        return [
            'title' => $title,
            'value' => $value,
            'icon' => strtok($title, ' '),
            'trend' => null,
            'trendDirection' => 'neutral',
            // null renders a non-clickable card; a URL renders a link.
            'link' => $link,
            'color' => $color,
            'description' => $description,
        ];
    }
}
