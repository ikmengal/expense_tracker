<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Mail\SupportReplyMail;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Support;
use App\Models\User;
use App\Models\Goal;

class AdminController extends Controller
{
    // System Analytics Data Engine
    public function getAnalytics(Request $request)
    {
        $totalUsers = User::role('User')->count();

        return response()->json([
            'total_users'    => $totalUsers,
            'total_goals'    => Goal::count(),
            'achieved_goals' => Goal::where('status', 'achieved')->count(),
            'open_tickets'   => Support::where('status', 'open')->count(),
        ]);
    }

    // Users Database Accounts List
    public function getUsers(Request $request)
    {
        return response()->json(
            User::with('roles')->withCount(['goals'])->latest()->get()
        );
    }

    // System Saving Goals Database Records
    public function getGoals(Request $request)
    {
        // Goals ke sath target users ka basic structural profile call
        return response()->json(
            Goal::with('user:id,name,email')->latest()->get()
        );
    }

    // Support Inquiries Tickets List
    public function getTickets(Request $request)
    {
        return response()->json(Support::with('user')->latest()->get());
    }

    // Ticket Resolution Action Engine
    public function replyToTicket(Request $request, $id){
        $request->validate([
            'reply' => 'required|string|min:3'
        ]);

        $ticket = Support::with('user')->findOrFail($id);
        $ticket->admin_reply = $request->reply;
        $ticket->status = 'resolved';
        $ticket->save();

        try {
            Mail::to($ticket->user->email)->send(new SupportReplyMail($ticket, $request->reply));
        } catch (\Exception $e) {
            Log::error("Mail fail hua: " . $e->getMessage());
        }
        return response()->json(['message' => 'Reply sent and logged successfully', 'ticket' => $ticket]);
    }

    // Global Settings Fetch (Bina prefix ke chalega)
    public function getGlobalSettings()
    {
        $setting = Setting::first();
        return response()->json($setting ?? [
            'site_name' => 'CASHFLOW',
            'site_logo' => null,
            'currency_symbol' => '$',
            'site_email' => 'admin@cashflow.com'
        ]);
    }

    // Admin Content Panel Settings Fetch
    public function getSettings()
    {
        // Agar table khali hai toh pehla record aapke naye columns ke sath create karega
        return response()->json(Setting::firstOrCreate(['id' => 1], [
            'site_name' => 'CASHFLOW',
            'currency_symbol' => '$',
            'site_email' => 'admin@cashflow.com'
        ]));
    }

    // System Wide Branding Update (Logo + Name)
    public function updateSettings(Request $request)
    {
        $request->validate([
            'site_name'       => 'required|string|max:255',
            'site_email'      => 'required|email|max:255',
            'currency_symbol' => 'required|string|max:10',
            'site_logo'       => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048'
        ]);

        $setting = Setting::firstOrCreate(['id' => 1]);

        $setting->site_name       = $request->site_name;
        $setting->site_email      = $request->site_email;
        $setting->currency_symbol = $request->currency_symbol;

        if ($request->hasFile('site_logo')) {
            // Purana logo storage se delete karne ke liye
            if ($setting->site_logo) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $setting->site_logo));
            }
            $path = $request->file('site_logo')->store('uploads', 'public');
            $setting->site_logo = asset('storage/' . $path);
        }

        $setting->save();
        return response()->json(['message' => 'Branding matrix updated successfully', 'settings' => $setting]);
    }

    // Update Admin Master Profile
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' // 'profile_picture' ko 'avatar' kar diya
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        // Handle Existing Avatar Upload
        if ($request->hasFile('avatar')) {
            // Purani file delete karne ke liye (Aapka database column 'avatar' use karega)
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Nayi image store karein (Yeh 'avatars/filename.jpg' return karega)
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path; // 💡 'avatar_url' ki jagah database column 'avatar' use kiya
        }
        $user->save();

        return response()->json(['message' => 'Profile details updated successfully', 'user' => $user]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'old_password' => 'required|string',
            'password'     => 'required|string|min:8|confirmed', // Yeh automatic password_confirmation dhoondega
        ]);

        // Pehle check karo ke purana password sahi hai ya nahi
        if (!Hash::check($request->old_password, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Current password galat hai.'],
            ]);
        }

        // Naya password save karo
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password security updated successfully']);
    }

    // Inside your Admin Controller (e.g., AdminController.php)
    public function impersonatingUser(Request $request, User $user)
    {
        try {
            // 1. Guard check: Admin khud ko impersonate na kare
            if (auth()->id() === $user->id) {
                return response()->json([
                    'message' => 'Aap khud apna dashboard to direct dekh hi sakte hain!'
                ], 422);
            }

            // 2. Short-lived token generate karein (Jo sirf 2 minutes ke liye valid ho)
            // Taake testing aur live environment dono me security tight rahe
            $token = $user->createToken(
                'impersonation_token',
                ['*'],
                now()->addMinutes(2)
            )->plainTextToken;

            // 3. System logs me secure entry daal dein taake audit trail rahe
            Log::info("Admin (ID: " . auth()->id() . ") started impersonating User (ID: {$user->id})");

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'redirect_url' => '/dashboard' // Vue app ka target dashboard view
            ], 200);

        } catch (\Exception $e) {
            Log::error("Impersonation Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Backend system token pipeline build nahi kar saka.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user accounts list with goals and transactions counts.
     */
    public function index()
    {
        $users = User::with('roles')
            ->withCount(['goals', 'transactions'])
            ->get();

        return response()->json($users, 200);
    }

    /**
     * Get deep analytical diagnostics financial metrics for a specific user.
     */
    public function getUserAnalytics($id)
    {
        // 1. Manually resolve user with explicit fail-safe
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'name' => 'Unknown User Profile',
                'total_income' => 'PKR 0.00',
                'total_expense' => 'PKR 0.00',
                'net_balance' => 'PKR 0.00',
                'recent_transactions' => [],
                'monthly_cashflow' => ['labels' => [], 'income' => [], 'expense' => []],
                'category_breakdown' => ['labels' => [], 'data' => []]
            ], 404);
        }

        // 2. Core Financial Breakdown Aggregations using Category Type Join
        $financials = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->select(
                DB::raw("SUM(CASE WHEN categories.type = 'income' THEN transactions.amount ELSE 0 END) as total_income"),
                DB::raw("SUM(CASE WHEN categories.type = 'expense' THEN transactions.amount ELSE 0 END) as total_expense")
            )
            ->first();

        $totalIncome = (float) ($financials->total_income ?? 0);
        $totalExpense = (float) ($financials->total_expense ?? 0);
        $netBalance = $totalIncome - $totalExpense;

        // 3. Recent Transactions Ledger with Category context & type indicator
        $recentTransactions = $user->transactions()
            ->with('category:id,name,type')
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(function($tx) {
                return [
                    'id' => $tx->id,
                    'description' => $tx->description,
                    'amount' => number_format($tx->amount, 2),
                    'type' => $tx->category->type ?? 'expense',
                    'currency' => $tx->currency ?? 'PKR',
                    'date' => $tx->date ? (is_string($tx->date) ? substr($tx->date, 0, 10) : $tx->date->format('Y-m-d')) : 'N/A',
                    'category' => $tx->category
                ];
            });

        // 4. Chart.js Bar Configuration: Monthly Cashflow Trend (Last 6 Months)
        $monthlyData = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->whereNotNull('transactions.date')
            ->select(
                DB::raw("DATE_FORMAT(transactions.date, '%b %Y') as month"),
                DB::raw("SUM(CASE WHEN categories.type = 'income' THEN transactions.amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN categories.type = 'expense' THEN transactions.amount ELSE 0 END) as expense"),
                DB::raw("YEAR(transactions.date) as r_year"),
                DB::raw("MONTH(transactions.date) as r_month")
            )
            ->groupBy(DB::raw("DATE_FORMAT(transactions.date, '%b %Y')"), DB::raw("YEAR(transactions.date)"), DB::raw("MONTH(transactions.date)"))
            ->orderBy('r_year', 'asc')
            ->orderBy('r_month', 'asc')
            ->limit(6)
            ->get();

        $cashflow = [
            'labels' => $monthlyData->pluck('month')->toArray(),
            'income' => $monthlyData->pluck('income')->map(fn($v) => (float)$v)->toArray(),
            'expense' => $monthlyData->pluck('expense')->map(fn($v) => (float)$v)->toArray(),
        ];

        // 5. Chart.js Doughnut Configuration: Category Expense Breakdown Distributions
        $categoryData = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->where('categories.type', 'expense')
            ->select('categories.name', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('categories.name')
            ->get();

        $categoryBreakdown = [
            'labels' => $categoryData->pluck('name')->toArray(),
            'data' => $categoryData->pluck('total')->map(fn($v) => (float)$v)->toArray(),
        ];

        return response()->json([
            'name' => $user->name,
            'total_income' => 'PKR ' . number_format($totalIncome, 2),
            'total_expense' => 'PKR ' . number_format($totalExpense, 2),
            'net_balance' => 'PKR ' . number_format($netBalance, 2),
            'recent_transactions' => $recentTransactions,
            'monthly_cashflow' => $cashflow,
            'category_breakdown' => $categoryBreakdown
        ], 200);
    }

    /**
     * System Control Center - Live Overview Stats
     */
    public function getSystemOverview()
    {
        $totalActiveUsers = User::count();

        $systemGoals = DB::table('goals')->count();
        $goalsAchieved = DB::table('goals')->where('current_amount', '>=', DB::raw('target_amount'))->count();

        // Dynamic stats payload for top summary cards
        return response()->json([
            'total_active_users' => $totalActiveUsers,
            'system_saving_goals' => $systemGoals,
            'goals_achieved' => $goalsAchieved,
            'open_complaints' => 0 // Fallback configuration or default handle
        ], 200);
    }

    /**
     * Modern Premium SaaS Workspace Data Aggregator
     */
    public function getUserSaaSAnalytics($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User workspace not found'], 404);
        }

        // 1. Sleek Wallet Metrics (Premium Card Style Context)
        $financials = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->select(
                DB::raw("SUM(CASE WHEN categories.type = 'income' THEN transactions.amount ELSE 0 END) as total_income"),
                DB::raw("SUM(CASE WHEN categories.type = 'expense' THEN transactions.amount ELSE 0 END) as total_expense")
            )
            ->first();

        $income = (float)($financials->total_income ?? 0);
        $expense = (float)($financials->total_expense ?? 0);
        $netBalance = $income - $expense;

        // Dynamic status mapping for the micro-alert components
        $expenseRatio = $income > 0 ? ($expense / $income) * 100 : 0;
        $riskLevel = 'Safe';
        $riskMessage = 'Healthy balance allocation.';

        if ($expenseRatio >= 90) {
            $riskLevel = 'Critical';
            $riskMessage = 'Critical condition! Expenses exceeded 90% threshold limits.';
        } elseif ($expenseRatio >= 80) {
            $riskLevel = 'Warning';
            $riskMessage = 'Alert: Running close to allocated limits.';
        }

        // 2. Center Panel: Dynamic Two-Column Monthly Cashflow Analytics
        $monthlyData = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->whereNotNull('transactions.date')
            ->select(
                DB::raw("DATE_FORMAT(transactions.date, '%b %Y') as month"),
                DB::raw("SUM(CASE WHEN categories.type = 'income' THEN transactions.amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN categories.type = 'expense' THEN transactions.amount ELSE 0 END) as expense"),
                DB::raw("YEAR(transactions.date) as r_year"),
                DB::raw("MONTH(transactions.date) as r_month")
            )
            ->groupBy(DB::raw("DATE_FORMAT(transactions.date, '%b %Y')"), DB::raw("YEAR(transactions.date)"), DB::raw("MONTH(transactions.date)"))
            ->orderBy('r_year', 'asc')
            ->orderBy('r_month', 'asc')
            ->limit(6)
            ->get();

        // 3. Center Panel: Donut Chart Distribution with Budget Limit Context
        $categoryData = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->where('categories.type', 'expense')
            ->select(
                'categories.name',
                'categories.budget_limit',
                DB::raw('SUM(transactions.amount) as total_spent')
            )
            ->groupBy('categories.name', 'categories.budget_limit')
            ->get()
            ->map(function($cat) {
                $limit = (float)($cat->budget_limit ?? 0);
                $spent = (float)$cat->total_spent;
                return [
                    'name' => $cat->name,
                    'value' => $spent,
                    'budget_limit' => $limit,
                    'is_breached' => $limit > 0 && $spent > $limit
                ];
            });

        // 4. Compact Activity Ledger Context (Recent Ledger Records)
        $recentLedger = $user->transactions()
            ->with('category:id,name,type,icon')
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($tx) => [
                'id' => $tx->id,
                'title' => $tx->description ?? ($tx->category->name . ' Transaction'),
                'amount' => (float)$tx->amount,
                'actual_currency_entry' => $tx->actual_amount . ' ' . ($tx->currency ?? 'PKR'),
                'type' => $tx->category->type ?? 'expense',
                'timestamp' => $tx->date ? (is_string($tx->date) ? substr($tx->date, 0, 10) : $tx->date->format('Y-m-d')) : 'N/A'
            ]);

        // Compact SaaS State Package
        return response()->json([
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar
            ],
            'wallet_state' => [
                'raw_income' => $income,
                'raw_expense' => $expense,
                'raw_net_balance' => $netBalance,
                'formatted_income' => 'PKR ' . number_format($income, 2),
                'formatted_expense' => 'PKR ' . number_format($expense, 2),
                'formatted_balance' => 'PKR ' . number_format($netBalance, 2),
                'threshold_metrics' => [
                    'ratio' => round($expenseRatio, 2),
                    'risk_level' => $riskLevel,
                    'message' => $riskMessage
                ]
            ],
            'charts' => [
                'cashflow_trend' => [
                    'labels' => $monthlyData->pluck('month')->toArray(),
                    'income_stream' => $monthlyData->pluck('income')->map(fn($v) => (float)$v)->toArray(),
                    'expense_stream' => $monthlyData->pluck('expense')->map(fn($v) => (float)$v)->toArray(),
                ],
                'expense_donut' => [
                    'labels' => $categoryData->pluck('name')->toArray(),
                    'dataset' => $categoryData->pluck('value')->toArray(),
                    'structural_details' => $categoryData
                ]
            ],
            'recent_ledger' => $recentLedger
        ], 200);
    }
}
