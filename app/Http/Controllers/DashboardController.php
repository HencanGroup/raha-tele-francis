<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // User growth metrics
        $totalUsers = User::count();
        $newUsersThisMonth = User::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $activeUsers = User::active()->count();
        $verifiedUsers = User::verified()->count();

        // Gender distribution
        $genderDistribution = User::select('gender', DB::raw('count(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get()
            ->mapWithKeys(fn($item) => [$item->gender => $item->count]);

        // Age distribution
        $ageDistribution = User::whereNotNull('birth_date')
            ->get()
            ->groupBy(function ($user) {
                $age = $user->age;
                if ($age < 18) return 'Under 18';
                if ($age < 25) return '18-24';
                if ($age < 35) return '25-34';
                if ($age < 45) return '35-44';
                if ($age < 55) return '45-54';
                return '55+';
            })
            ->map->count();

        // User status distribution
        $statusDistribution = User::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status => $item->count]);

        // Monthly user signups
        $monthlySignups = User::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
            DB::raw('count(*) as count')
        )
            ->where('created_at', '>=', Carbon::now()->subYear())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Verification status
        $verificationStatus = [
            'email_verified' => User::whereNotNull('email_verified_at')->count(),
            'phone_verified' => User::where('phone_verified', true)->count(),
            'profile_verified' => User::where('is_verified', true)->count(),
        ];

        // Subscription metrics
        $subscriptionStats = [
            'with_subscription' => User::withActiveSubscription()->count(),
            'without_subscription' => $totalUsers - User::withActiveSubscription()->count(),
        ];

        // Location distribution (top 10)
        $locationDistribution = User::select('location', DB::raw('count(*) as count'))
            ->whereNotNull('location')
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Backend/Dashboard', [
            'metrics' => [
                'total_users' => $totalUsers,
                'new_users_month' => $newUsersThisMonth,
                'active_users' => $activeUsers,
                'verified_users' => $verifiedUsers,
            ],
            'charts' => [
                'gender_distribution' => $genderDistribution,
                'age_distribution' => $ageDistribution,
                'status_distribution' => $statusDistribution,
                'monthly_signups' => $monthlySignups,
                'verification_status' => $verificationStatus,
                'subscription_stats' => $subscriptionStats,
                'location_distribution' => $locationDistribution,
            ],
        ]);
    }
}
