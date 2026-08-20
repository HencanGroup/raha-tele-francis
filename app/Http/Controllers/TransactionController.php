<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Credit ledger listing for the dashboard — members see their spending
 * history, escorts see their earnings history.
 */
class TransactionController extends Controller
{
    /**
     * Render the credit transactions page for the authenticated user.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();

        // Paginate the ledger — 5 rows per page by default; keep the query
        // string so page links survive filters.
        $transactions = CreditTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->through(fn ($tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => $tx->amount,
                'balance_before' => $tx->balance_before,
                'balance_after' => $tx->balance_after,
                'description' => $tx->description,
                'created_at' => $tx->created_at,
            ]);

        return Inertia::render('Backend/Dashboard/Transactions', [
            'transactions' => $transactions,
            'isMember' => $user->isMember(),
        ]);
    }
}
