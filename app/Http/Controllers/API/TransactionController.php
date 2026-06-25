<?php

namespace App\Http\Controllers\API;

use App\Notifications\BudgetAlertNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use App\Models\RecurringBill;
use App\Models\Transaction;
use App\Models\Category;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Frontend se aayi hui currency query param se pakrein ya user ki default set karein
        $targetCurrency = $request->query('currency', $user->default_currency ?? 'PKR');

        // Saari transactions load karein
        $transactions = $user->transactions()
            ->with('category')
            ->orderBy('date', 'desc')
            ->get();

        // Har transaction ki original amount ko target currency se replace karein
        $transactions->transform(function ($t) use ($targetCurrency) {
            $txCurrency = $t->currency ?? 'PKR';

            // 1. Database se base amount (PKR) ko target currency mein convert karein
            $convertedAmount = CurrencyService::convert($t->amount, 'PKR', $targetCurrency);

            // 2. CRITICAL FIX: Asli 'amount' field ko hi converted amount se override kar dein
            // Taake frontend par bina kisi change ke converted value dikhe
            $t->amount = round($convertedAmount, 2);

            // Aapki asani ke liye currency property ko bhi active currency bana dete hain
            $t->currency = $targetCurrency;

            return $t;
        });

        return response()->json($transactions, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1', // Yeh form se aane wala actual amount hy
            'category_id' => 'required|exists:categories,id',
            'currency' => 'required|string|max:3',
            'date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $user = $request->user();
        $formAmount = $request->amount; // Actual value from form (e.g. 100)
        $inputCurrency = strtoupper($request->currency);
        $baseCurrency = 'PKR';
        $rate = 1.000000;

        // 🔄 Live Free Currency Exchange Rate Engine
        if ($inputCurrency !== $baseCurrency) {
            try {
                $response = Http::timeout(6)->get("https://open.er-api.com/v6/latest/USD");
                if ($response->successful()) {
                    $rates = $response->json()['rates'] ?? [];
                    if (isset($rates[$baseCurrency]) && isset($rates[$inputCurrency])) {
                        $usdToPkr = $rates[$baseCurrency];
                        $usdToInput = $rates[$inputCurrency];
                        $rate = $usdToPkr / $usdToInput;
                    }
                }
            } catch (\Exception $e) {
                $fallbacks = ['USD' => 278.40, 'EUR' => 301.15, 'AED' => 75.81];
                $rate = $fallbacks[$inputCurrency] ?? 1.000000;
            }
        }

        // Mathematical computations for storage logic
        $convertedAmount = $formAmount * $rate; // Converted PKR Value

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'category_id' => $request->category_id,
            'amount' => $convertedAmount,      // System computations & reports ke liye (PKR)
            'actual_amount' => $formAmount,    // 👈 Actual form value (e.g., 100.00)
            'currency' => $inputCurrency,      // Selected currency (e.g., AED)
            'exchange_rate' => $rate,          // Conversion factor (e.g., 75.81)
            'description' => $request->description,
            'date' => $request->date,
        ]);

        // Eloquent relationship load ko explicitly force karwa rahe hain taake log safe rahein
        $transaction->load('category');

        if ($transaction->category) {
            Log::info('[BUDGET CHECK: CATEGORY FOUND]', [
                'category_name' => $transaction->category->name,
                'type'          => $transaction->category->type,
                'budget_limit'  => $transaction->category->budget_limit
            ]);

            if ($transaction->category->type === 'expense' && $transaction->category->budget_limit > 0) {
                $currentMonth = date('Y-m');

                // Calculate total spent inside this specific category
                $totalSpent = Transaction::where('user_id', $user->id)
                    ->where('category_id', $transaction->category_id)
                    ->where('date', 'like', $currentMonth . '%')
                    ->sum('amount');

                $limit = $transaction->category->budget_limit;
                $percentage = ($totalSpent / $limit) * 100;

                // 📝 LOG 2: Budget Evaluation Numbers Matrix
                Log::info('[BUDGET EVALUATION]', [
                    'current_month' => $currentMonth,
                    'total_spent'   => $totalSpent,
                    'budget_limit'  => $limit,
                    'computed_pct'  => round($percentage, 2) . '%'
                ]);

                if ($percentage >= 80) {
                    try {
                        // Dispatch Notification if warning condition meets
                        $user->notify(new BudgetAlertNotification([
                            'category_name' => $transaction->category->name,
                            'percentage'    => round($percentage, 2)
                        ]));

                        // 📝 LOG 3: Notification Fire Success Safeguard
                        Log::info('[BUDGET ALERT DISPATCHED]', [
                            'user_id'       => $user->id,
                            'category_name' => $transaction->category->name,
                            'percentage'    => round($percentage, 2)
                        ]);
                    } catch (\Exception $e) {
                        // 📝 LOG 4: Failures Capture Pipeline
                        Log::error('[BUDGET ALERT CRASHED]', [
                            'error_message' => $e->getMessage(),
                            'trace'         => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    Log::info('[BUDGET SAFE]: Percentage threshold 80% se kam hai.');
                }
            } else {
                Log::info('[BUDGET BYPASSED]: Category expense type nahi hai ya budget limit zero hai.');
            }
        } else {
            // 📝 LOG 5: Relationship Leak Trace
            Log::warning('[BUDGET CHECK FAILED]: Transaction category relation load nahi ho saki. Schema verify karein.');
        }

        return response()->json([
            'message' => 'Transaction saved successfully',
            'data'    => $transaction
        ], 201);
    }

    /**
     * Display the specified transaction.
     */
    public function show(Request $request, Transaction $transaction)
    {
        $user = $request->user();
        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $targetCurrency = $request->query('currency', $user->default_currency ?? 'PKR');

        // Single view mein bhi original 'amount' field ko override karein
        $convertedAmount = CurrencyService::convert($transaction->amount, 'PKR', $targetCurrency);

        $transaction->load('category');
        $transaction->amount = round($convertedAmount, 2);
        $transaction->currency = $targetCurrency;

        return response()->json($transaction, 200);
    }

    /**
     * Update the specified transaction in storage.
     */
    public function update(Request $request, $id){
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'category_id' => 'required|exists:categories,id',
            'currency' => 'required|string|max:3',
            'date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $user = $request->user();
        $transaction = Transaction::where('user_id', $user->id)->find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $formAmount = $request->amount;
        $inputCurrency = strtoupper($request->currency);
        $baseCurrency = 'PKR';
        $rate = 1.000000;

        // 🔄 Live Free Currency Exchange Rate Engine for Updates
        if ($inputCurrency !== $baseCurrency) {
            try {
                $response = Http::timeout(6)->get("https://open.er-api.com/v6/latest/USD");
                if ($response->successful()) {
                    $rates = $response->json()['rates'] ?? [];
                    if (isset($rates[$baseCurrency]) && isset($rates[$inputCurrency])) {
                        $usdToPkr = $rates[$baseCurrency];
                        $usdToInput = $rates[$inputCurrency];
                        $rate = $usdToPkr / $usdToInput;
                    }
                }
            } catch (\Exception $e) {
                $fallbacks = ['USD' => 278.40, 'EUR' => 301.15, 'AED' => 75.81];
                $rate = $fallbacks[$inputCurrency] ?? 1.000000;
            }
        }

        // Mathematical Recalculations
        $convertedAmount = $formAmount * $rate;

        $transaction->update([
            'category_id' => $request->category_id,
            'amount' => $convertedAmount,       // Recalculated baseline PKR
            'actual_amount' => $formAmount,    // New inputted actual amount
            'currency' => $inputCurrency,
            'exchange_rate' => $rate,
            'description' => $request->description,
            'date' => $request->date,
        ]);

        return response()->json([
            'message' => 'Transaction updated successfully',
            'data' => $transaction
        ], 200);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->delete();
        return response()->json(['message' => 'Transaction deleted successfully'], 200);
    }

    // FRONTEND SPECIAL: Dashboard stats method
    // public function dashboardStats(Request $request)
    // {
    //     $user = $request->user();
    //     $targetCurrency = $request->get('currency', $user->default_currency ?? 'PKR');

    //     // Dates calculations
    //     $now = Carbon::now();
    //     $startOfMonth = $now->copy()->startOfMonth();
    //     $endOfMonth = $now->copy()->endOfMonth();
    //     $startOfYear = $now->copy()->startOfYear();
    //     $endOfYear = $now->copy()->endOfYear();

    //     // PERFORMANCE OPTIMIZATION: Poore saal ki saari transactions ek hi query me fetch karein
    //     $yearlyTransactions = $user->transactions()
    //         ->with('category')
    //         ->whereBetween('date', [$startOfYear, $endOfYear])
    //         ->get();

    //     // 🌟 Lifetime Net Balance calculation ke liye explicit database aggregations (Fast execution)
    //     $lifetimeIncome = $user->transactions()->whereHas('category', function($q) { $q->where('type', 'income'); })->sum('amount');
    //     $lifetimeExpense = $user->transactions()->whereHas('category', function($q) { $q->where('type', 'expense'); })->sum('amount');

    //     // Convert base raw currencies to targeted layout metrics
    //     $rawNetBalance = $lifetimeIncome - $lifetimeExpense;
    //     // Assuming base default currency fallback conversion layer
    //     $convertedNetBalance = CurrencyService::convert($rawNetBalance, $user->default_currency ?? 'PKR', $targetCurrency);

    //     // 1. Current Month Summary Context Data Initialize
    //     $totalIncome = 0;
    //     $totalExpense = 0;

    //     // 2. Category Expense Breakdown Data Initialize (Current Month Only)
    //     $categoryWiseExpense = [];

    //     // 3. Yearly Analytics Variables Initialize
    //     $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    //     $monthlyIncome = array_fill(0, 12, 0);
    //     $monthlyExpense = array_fill(0, 12, 0);

    //     // Filter and Process Collection Matrix
    //     foreach ($yearlyTransactions as $t) {
    //         if (!$t->category) continue;

    //         $txCurrency = $t->currency ?? 'PKR';
    //         $convertedAmount = CurrencyService::convert($t->amount, $txCurrency, $targetCurrency);
    //         $t->converted_amount = round($convertedAmount, 2);

    //         $txDate = Carbon::parse($t->date);
    //         $monthIndex = $txDate->month - 1; // 0 for Jan, 11 for Dec

    //         // A. Populate Yearly Graph Matrix Data Structure
    //         if ($t->category->type === 'income') {
    //             $monthlyIncome[$monthIndex] += $convertedAmount;
    //         } else {
    //             $monthlyExpense[$monthIndex] += $convertedAmount;
    //         }

    //         // B. Populate Current Month Only Stats & Category Breakdown Tracker
    //         if ($txDate->between($startOfMonth, $endOfMonth)) {
    //             if ($t->category->type === 'income') {
    //                 $totalIncome += $convertedAmount;
    //             } else {
    //                 $totalExpense += $convertedAmount;

    //                 // Structure format: "📁 Category Name" matching view pattern
    //                 $catKey = ($t->category->icon ?? '📁') . ' ' . $t->category->name;
    //                 if (!isset($categoryWiseExpense[$catKey])) {
    //                     $categoryWiseExpense[$catKey] = 0;
    //                 }
    //                 $categoryWiseExpense[$catKey] += $convertedAmount;
    //             }
    //         }
    //     }

    //     // Recent Transactions Collection filtering from current month dataset
    //     $recentTransactions = $yearlyTransactions
    //         ->filter(function ($t) use ($startOfMonth, $endOfMonth) {
    //             return Carbon::parse($t->date)->between($startOfMonth, $endOfMonth);
    //         })
    //         ->sortByDesc('date')
    //         ->take(10)
    //         ->values();

    //     // Precision Rounding Loops mapping logic safely array filters
    //     foreach ($categoryWiseExpense as $key => $value) {
    //         $categoryWiseExpense[$key] = round($value, 2);
    //     }

    //     $monthlyIncome = array_map(fn($v) => round($v, 2), $monthlyIncome);
    //     $monthlyExpense = array_map(fn($v) => round($v, 2), $monthlyExpense);

    //     // --- Upcoming Bills Logic ---
    //     $upcomingBills = [];
    //     $today = Carbon::now()->toDateString();
    //     $sevenDaysFromNow = Carbon::now()->addDays(7)->toDateString();

    //     // Sirf unpaid bills uthao jo aglay 7 din me due hone wale hain
    //     $bills = $user->recurringBills()
    //         ->with('category')
    //         ->where('status', 'unpaid')
    //         ->whereBetween('due_date', [$today, $sevenDaysFromNow])
    //         ->orderBy('due_date', 'asc')
    //         ->get();

    //     foreach ($bills as $bill) {
    //         $billCurrency = $bill->currency ?? 'PKR';
    //         $convertedBillAmount = CurrencyService::convert($bill->amount, $billCurrency, $targetCurrency);

    //         // Kitne din baqi hain calculate karo
    //         $daysLeft = (int) Carbon::now()->startOfDay()->diffInDays(Carbon::parse($bill->due_date)->startOfDay(), false);

    //         $upcomingBills[] = [
    //             'id' => $bill->id,
    //             'name' => $bill->name,
    //             'amount' => round($convertedBillAmount, 2),
    //             'currency' => $targetCurrency,
    //             'due_date' => Carbon::parse($bill->due_date)->format('d M'),
    //             'days_left' => $daysLeft,
    //             'category_icon' => $bill->category->icon ?? '💳'
    //         ];
    //     }

    //     // 1. Check karein ke kya user is mahine pehle hi AI Smart Save use kar chuka hai?
    //     $hasAlreadySavedThisMonth = $user->transactions()
    //         ->whereBetween('date', [$startOfMonth, $endOfMonth])
    //         ->where('description', 'LIKE', '🤖 AI Smart Save%')
    //         ->exists();

    //     // 🤖 GENERATE EMBEDDED REAL-TIME AI INSIGHTS RULES
    //     // 3. Normal general insights text logic
    //     $safeToSpendToday = max(0, ($convertedNetBalance * 0.05) / 7);
    //     $insightMessage = "💡 AI Insight: Safe to spend {$targetCurrency} " . number_format($safeToSpendToday, 2) . " today based on available wallets.";

    //     if ($totalExpense > ($totalIncome * 0.8)) {
    //         $insightMessage = "⚠️ Alert: This month's expenses have reached 80% of your income. Tighten budgets!";
    //     }

    //     // 2. Active Goal ki checking
    //     $activeGoal = $user->goals()->where('status', 'active')->first();
    //     $goalForecast = "Set up an active savings milestone target to unlock predictive tracking timelines.";
    //     $recommendedTransfer = 0;

    //     if ($activeGoal) {
    //         // ✨ AGAR USER PEHLE HI TRANSFER KAR CHUKA HAI:
    //         if ($hasAlreadySavedThisMonth) {
    //             $goalForecast = "🎉 Outstanding! Your targeted budget milestone allocation for this period has been fully funded.";
    //             $recommendedTransfer = 0; // 💥 Is se frontend ka v-if button ko automatic hide kar dega!
    //         } else {
    //             // Agar pehle transfer nahi kiya, to normal math calculation chalao
    //             $convertedGoalTarget = CurrencyService::convert(($activeGoal->target_amount - $activeGoal->current_amount), $activeGoal->currency ?? 'PKR', $targetCurrency);

    //             // Raw balance ka 12% calculate karo
    //             $recommendedTransfer = min($convertedNetBalance * 0.12, $convertedGoalTarget);

    //             if ($recommendedTransfer > 0) {
    //                 $goalForecast = "AI forecast suggests allocating {$targetCurrency} " . number_format($recommendedTransfer, 2) . " to reach your target ahead of schedule.";
    //             } else {
    //                 $goalForecast = "🎉 Outstanding! Your targets are fully funded based on today's account records.";
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'summary' => [
    //             'total_income' => round($totalIncome, 2),
    //             'total_expense' => round($totalExpense, 2),
    //             // 'net_balance' => round($totalIncome - $totalExpense, 2),
    //             'net_balance' => round($convertedNetBalance, 2),
    //             'month' => $now->format('F Y'),
    //             'currency' => $targetCurrency
    //         ],
    //         'ai_insights' => [
    //             'insight_text' => $insightMessage,
    //             'goal_forecast' => $goalForecast,
    //             'recommended_goal_id' => $activeGoal ? $activeGoal->id : null,
    //             'recommended_transfer_amount' => round($recommendedTransfer, 2)
    //         ],
    //         'monthly_cashflow' => [
    //             'labels' => $months,
    //             'income' => $monthlyIncome,
    //             'expense' => $monthlyExpense
    //         ],
    //         'category_breakdown' => [
    //             'labels' => array_keys($categoryWiseExpense),
    //             'data' => array_values($categoryWiseExpense)
    //         ],
    //         'recent_transactions' => $recentTransactions,
    //         'upcoming_bills' => $upcomingBills
    //     ], 200);
    // }

    public function dashboardStats(Request $request)
    {
        $user = $request->user();
        $targetCurrency = $request->get('currency', $user->default_currency ?? 'PKR');

        // 📅 Dynamic Month & Year Filters with Fallback to Current Time
        $selectedMonth = (int) $request->get('month', Carbon::now()->month);
        $selectedYear = (int) $request->get('year', Carbon::now()->year);

        // Filter ke mutabiq explicit boundaries calculate karein
        $targetDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();

        // Graph pure saal ka chalta hai lekin selected year ke hisab se dynamic hoga
        $startOfYear = Carbon::createFromDate($selectedYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($selectedYear, 12, 31)->endOfYear();

        // PERFORMANCE OPTIMIZATION: Selected year ki saari transactions fetch karein
        $yearlyTransactions = $user->transactions()
            ->with('category')
            ->whereBetween('date', [$startOfYear, $endOfYear])
            ->get();

        // 🌟 Lifetime Net Balance (Always absolute aggregation)
        $lifetimeIncome = $user->transactions()->whereHas('category', function($q) { $q->where('type', 'income'); })->sum('amount');
        $lifetimeExpense = $user->transactions()->whereHas('category', function($q) { $q->where('type', 'expense'); })->sum('amount');

        $rawNetBalance = $lifetimeIncome - $lifetimeExpense;
        $convertedNetBalance = CurrencyService::convert($rawNetBalance, $user->default_currency ?? 'PKR', $targetCurrency);

        // Initializations
        $totalIncome = 0;
        $totalExpense = 0;
        $categoryWiseExpense = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyIncome = array_fill(0, 12, 0);
        $monthlyExpense = array_fill(0, 12, 0);

        // Process Matrix
        foreach ($yearlyTransactions as $t) {
            if (!$t->category) continue;

            $txCurrency = $t->currency ?? 'PKR';
            $convertedAmount = CurrencyService::convert($t->amount, $txCurrency, $targetCurrency);
            $t->converted_amount = round($convertedAmount, 2);

            $txDate = Carbon::parse($t->date);
            $monthIndex = $txDate->month - 1;

            // A. Populate Dynamic Year Graph
            if ($t->category->type === 'income') {
                $monthlyIncome[$monthIndex] += $convertedAmount;
            } else {
                $monthlyExpense[$monthIndex] += $convertedAmount;
            }

            // B. Populate Selected Month Context Matrix Data
            if ($txDate->between($startOfMonth, $endOfMonth)) {
                if ($t->category->type === 'income') {
                    $totalIncome += $convertedAmount;
                } else {
                    $totalExpense += $convertedAmount;

                    $catKey = ($t->category->icon ?? '📁') . ' ' . $t->category->name;
                    if (!isset($categoryWiseExpense[$catKey])) {
                        $categoryWiseExpense[$catKey] = 0;
                    }
                    $categoryWiseExpense[$catKey] += $convertedAmount;
                }
            }
        }

        // Recent Transactions Filtered by Selected Month Window
        $recentTransactions = $yearlyTransactions
            ->filter(function ($t) use ($startOfMonth, $endOfMonth) {
                return Carbon::parse($t->date)->between($startOfMonth, $endOfMonth);
            })
            ->sortByDesc('date')
            ->take(10)
            ->values();

        foreach ($categoryWiseExpense as $key => $value) {
            $categoryWiseExpense[$key] = round($value, 2);
        }

        $monthlyIncome = array_map(fn($v) => round($v, 2), $monthlyIncome);
        $monthlyExpense = array_map(fn($v) => round($v, 2), $monthlyExpense);

        // --- Upcoming Bills (Static for immediate actions) ---
        $upcomingBills = [];
        $today = Carbon::now()->toDateString();
        $sevenDaysFromNow = Carbon::now()->addDays(7)->toDateString();

        $bills = $user->recurringBills()
            ->with('category')
            ->where('status', 'unpaid')
            ->whereBetween('due_date', [$today, $sevenDaysFromNow])
            ->orderBy('due_date', 'asc')
            ->get();

        foreach ($bills as $bill) {
            $billCurrency = $bill->currency ?? 'PKR';
            $convertedBillAmount = CurrencyService::convert($bill->amount, $billCurrency, $targetCurrency);
            $daysLeft = (int) Carbon::now()->startOfDay()->diffInDays(Carbon::parse($bill->due_date)->startOfDay(), false);

            $upcomingBills[] = [
                'id' => $bill->id,
                'name' => $bill->name,
                'amount' => round($convertedBillAmount, 2),
                'currency' => $targetCurrency,
                'due_date' => Carbon::parse($bill->due_date)->format('d M'),
                'days_left' => $daysLeft,
                'category_icon' => $bill->category->icon ?? '💳'
            ];
        }

        // 🤖 AI Real-Time Insights Rules targeting current selection context
        $hasAlreadySavedThisMonth = $user->transactions()
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('description', 'LIKE', '🤖 AI Smart Save%')
            ->exists();

        $safeToSpendToday = max(0, ($convertedNetBalance * 0.05) / 7);
        $insightMessage = "💡 AI Insight: Safe to spend {$targetCurrency} " . number_format($safeToSpendToday, 2) . " today based on available wallets.";

        if ($totalExpense > ($totalIncome * 0.8)) {
            $insightMessage = "⚠️ Alert: Expenses for this period have reached 80% of your income. Tighten budgets!";
        }

        $activeGoal = $user->goals()->where('status', 'active')->first();
        $goalForecast = "Set up an active savings milestone target to unlock predictive tracking timelines.";
        $recommendedTransfer = 0;

        if ($activeGoal) {
            if ($hasAlreadySavedThisMonth) {
                $goalForecast = "🎉 Outstanding! Your targeted budget milestone allocation for this period has been fully funded.";
                $recommendedTransfer = 0;
            } else {
                $convertedGoalTarget = CurrencyService::convert(($activeGoal->target_amount - $activeGoal->current_amount), $activeGoal->currency ?? 'PKR', $targetCurrency);
                $recommendedTransfer = min($convertedNetBalance * 0.12, $convertedGoalTarget);

                if ($recommendedTransfer > 0) {
                    $goalForecast = "AI forecast suggests allocating {$targetCurrency} " . number_format($recommendedTransfer, 2) . " to reach your target ahead of schedule.";
                } else {
                    $goalForecast = "🎉 Outstanding! Your targets are fully funded based on today's account records.";
                }
            }
        }

        return response()->json([
            'summary' => [
                'total_income' => round($totalIncome, 2),
                'total_expense' => round($totalExpense, 2),
                // 'net_balance' => round($convertedNetBalance, 2),
                'net_balance'   => round($totalIncome - $totalExpense, 2),
                'month' => $targetDate->format('F Y'),
                'currency' => $targetCurrency
            ],
            'ai_insights' => [
                'insight_text' => $insightMessage,
                'goal_forecast' => $goalForecast,
                'recommended_goal_id' => $activeGoal ? $activeGoal->id : null,
                'recommended_transfer_amount' => round($recommendedTransfer, 2)
            ],
            'monthly_cashflow' => [
                'labels' => $months,
                'income' => $monthlyIncome,
                'expense' => $monthlyExpense
            ],
            'category_breakdown' => [
                'labels' => array_keys($categoryWiseExpense),
                'data' => array_values($categoryWiseExpense)
            ],
            'recent_transactions' => $recentTransactions,
            'upcoming_bills' => $upcomingBills
        ], 200);
    }

    public function updateCurrency(Request $request)
    {
        $request->validate(['currency' => 'required|string|max:3']);
        $user = $request->user();
        $user->default_currency = $request->currency;
        $user->save();

        return response()->json(['message' => 'Currency preference updated successfully!']);
    }

    // public function scanReceipt(Request $request) {
    //     $request->validate([
    //         'image' => 'nullable|image|max:5120',
    //         'text_prompt' => 'nullable|string'
    //     ]);

    //     // try {
    //     //     $file = $request->file('image');
    //     //     $mimeType = $file->getMimeType();
    //     //     $imageData = base64_encode(file_get_contents($file));

    //     //     $prompt = "Analyze this receipt image. Extract the total bill amount, the store/merchant name, and the date of transaction.
    //     //     Return ONLY a clean JSON object exactly with these keys, no markdown text blocks, no backticks:
    //     //     {
    //     //         \"amount\": 0.00,
    //     //         \"description\": \"Store name or items description here\",
    //     //         \"date\": \"YYYY-MM-DD\"
    //     //     }";

    //     //     // 🔥 FIX: Headers aur Model Name aapke curl ke mutabiq exact set kar diye hain
    //     //     $response = Http::withHeaders([
    //     //         'Content-Type' => 'application/json',
    //     //         'X-goog-api-key' => env('GEMINI_API_KEY') // Aapki AQ.Ab8RN... waali key .env se load hogi
    //     //     ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent", [
    //     //         "contents" => [
    //     //             [
    //     //                 "parts" => [
    //     //                     ["text" => $prompt],
    //     //                     [
    //     //                         "inline_data" => [
    //     //                             "mime_type" => $mimeType,
    //     //                             "data" => $imageData
    //     //                         ]
    //     //                     ]
    //     //                 ]
    //     //             ]
    //     //         ]
    //     //     ]);

    //     //     if ($response->failed()) {
    //     //         Log::error("Gemini Final Error Body: " . $response->body());
    //     //         return response()->json(['status' => 'error', 'message' => 'Gemini API connection failed.'], 502);
    //     //     }

    //     //     $rawText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';

    //     //     // Clean markdown wrapper backticks if any
    //     //     $cleanJson = trim(str_replace(['```json', '```'], '', $rawText));
    //     //     $parsedData = json_decode($cleanJson, true);

    //     //     if (!$parsedData) {
    //     //         return response()->json([
    //     //             'status' => 'error',
    //     //             'message' => 'AI structure parsing failed.',
    //     //             'raw' => $rawText
    //     //         ], 422);
    //     //     }

    //     //     return response()->json([
    //     //         'status' => 'success',
    //     //         'data' => $parsedData
    //     //     ], 200);

    //     // } catch (\Exception $e) {
    //     //     Log::error("Receipt AI Error: " . $e->getMessage());
    //     //     return response()->json(['status' => 'error', 'message' => 'Server error during AI scanning.'], 500);
    //     // }

    //     try {
    //         $prompt = "Analyze the input data. Extract exactly three fields: the total numeric amount, a short clean description/merchant name, and the transaction date.
    //         If date is not found or mentions today, use '" . date('Y-m-d') . "'.
    //         Return ONLY a clean JSON object exactly with these keys, no markdown, no backticks:
    //         {
    //             \"amount\": 0.00,
    //             \"description\": \"Clean description here\",
    //             \"date\": \"YYYY-MM-DD\"
    //         }";

    //         $payload = [
    //             "contents" => [
    //                 [
    //                     "parts" => [
    //                         ["text" => $prompt]
    //                     ]
    //                 ]
    //             ]
    //         ];

    //         // 1. Agar Image aayi hai (Camera/Upload)
    //         if ($request->hasFile('image')) {
    //             $file = $request->file('image');
    //             $mimeType = $file->getMimeType();
    //             $imageData = base64_encode(file_get_contents($file));

    //             $payload["contents"][0]["parts"][] = [
    //                 "inline_data" => [
    //                     "mime_type" => $mimeType,
    //                     "data" => $imageData
    //                 ]
    //             ];
    //         }
    //         // 2. Agar Voice Text aaya hai
    //         unset($request['image']); // cleaning pointer
    //         if ($request->input('text_prompt')) {
    //             $payload["contents"][0]["parts"][] = [
    //                 "text" => "Voice Text Input: " . $request->input('text_prompt')
    //             ];
    //         }

    //         // Gemini API Request
    //         $response = Http::withHeaders([
    //             'Content-Type' => 'application/json',
    //             'X-goog-api-key' => env('GEMINI_API_KEY')
    //         ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent", $payload);

    //         if ($response->failed()) {
    //             Log::error("Gemini Error: " . $response->body());
    //             return response()->json(['status' => 'error', 'message' => 'Gemini API connection failed.'], 502);
    //         }

    //         $rawText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
    //         $cleanJson = trim(str_replace(['```json', '```'], '', $rawText));
    //         $parsedData = json_decode($cleanJson, true);

    //         if (!$parsedData) {
    //             return response()->json(['status' => 'error', 'message' => 'AI parsing failed.'], 422);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $parsedData
    //         ], 200);

    //     } catch (\Exception $e) {
    //         Log::error("AI System Error: " . $e->getMessage());
    //         return response()->json(['status' => 'error', 'message' => 'Server error during scanning.'], 500);
    //     }
    // }

    public function scanReceipt(Request $request) {
        // 1. Initial Structural Request Validation
        $request->validate([
            'image'       => 'nullable|image|max:5120',
            'text_prompt' => 'nullable|string',
            'category_id' => 'required|exists:categories,id', // User input fallback validation
            'currency'    => 'required|string|max:3'
        ]);

        try {
            $prompt = "Analyze the input data. Extract exactly three fields: the total numeric amount, a short clean description/merchant name, and the transaction date.
            If date is not found or mentions today, use '" . date('Y-m-d') . "'.
            Return ONLY a clean JSON object exactly with these keys, no markdown, no backticks:
            {
                \"amount\": 0.00,
                \"description\": \"Clean description here\",
                \"date\": \"YYYY-MM-DD\"
            }";

            $payload = [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $prompt]
                        ]
                    ]
                ]
            ];

            // Process File Image if sent
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $mimeType = $file->getMimeType();
                $imageData = base64_encode(file_get_contents($file));

                $payload["contents"][0]["parts"][] = [
                    "inline_data" => [
                        "mime_type" => $mimeType,
                        "data" => $imageData
                    ]
                ];
            }
            // Process Text Voice if sent
            elseif ($request->input('text_prompt')) {
                $payload["contents"][0]["parts"][] = [
                    "text" => "Voice Text Input: " . $request->input('text_prompt')
                ];
            } else {
                return response()->json(['status' => 'error', 'message' => 'No image or text source provided.'], 422);
            }

            // Gemini Context Target Execution Call
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => env('GEMINI_API_KEY')
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent", $payload);

            if ($response->failed()) {
                Log::error("Gemini Failure Logs: " . $response->body());
                return response()->json(['status' => 'error', 'message' => 'Gemini API connection failed.'], 502);
            }

            $rawText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $cleanJson = trim(str_replace(['```json', '```'], '', $rawText));
            $aiParsed = json_decode($cleanJson, true);

            if (!$aiParsed || !isset($aiParsed['amount'])) {
                return response()->json(['status' => 'error', 'message' => 'AI failed to parse transactional metrics.'], 422);
            }

            // 2. 🔄 Currency Conversion Layer Integration (Using your exact logic)
            $user = $request->user();
            $formAmount = (float) $aiParsed['amount'];
            $inputCurrency = strtoupper($request->currency);
            $baseCurrency = 'PKR';
            $rate = 1.000000;

            if ($inputCurrency !== $baseCurrency) {
                try {
                    $exchangeResponse = Http::timeout(6)->get("https://open.er-api.com/v6/latest/USD");
                    if ($exchangeResponse->successful()) {
                        $rates = $exchangeResponse->json()['rates'] ?? [];
                        if (isset($rates[$baseCurrency]) && isset($rates[$inputCurrency])) {
                            $usdToPkr = $rates[$baseCurrency];
                            $usdToInput = $rates[$inputCurrency];
                            $rate = $usdToPkr / $usdToInput;
                        }
                    }
                } catch (\Exception $e) {
                    $fallbacks = ['USD' => 278.40, 'EUR' => 301.15, 'AED' => 75.81];
                    $rate = $fallbacks[$inputCurrency] ?? 1.000000;
                }
            }

            $convertedAmount = $formAmount * $rate; // Converted PKR Value

            // 3. 💾 Save direct to database instance
            $transaction = Transaction::create([
                'user_id'        => $user->id,
                'category_id'    => $request->category_id,
                'amount'         => $convertedAmount,      // Computational Engine Base (PKR)
                'actual_amount'  => $formAmount,           // Scanned real raw amount from AI
                'currency'       => $inputCurrency,
                'exchange_rate'  => $rate,
                'description'    => $request->input('text_prompt') ? 'Voice AI: ' . $aiParsed['description'] : 'Image AI Scan: ' . $aiParsed['description'],
                'date'           => $aiParsed['date'],
            ]);

            // 4. 📊 Budget Threshold Evaluation System (Your exact logic)
            $transaction->load('category');

            if ($transaction->category) {
                Log::info('[BUDGET CHECK: CATEGORY FOUND]', [
                    'category_name' => $transaction->category->name,
                    'type'          => $transaction->category->type,
                    'budget_limit'  => $transaction->category->budget_limit
                ]);

                if ($transaction->category->type === 'expense' && $transaction->category->budget_limit > 0) {
                    $currentMonth = date('Y-m');
                    $totalSpent = Transaction::where('user_id', $user->id)
                        ->where('category_id', $transaction->category_id)
                        ->where('date', 'like', $currentMonth . '%')
                        ->sum('amount');

                    $limit = $transaction->category->budget_limit;
                    $percentage = ($totalSpent / $limit) * 100;

                    Log::info('[BUDGET EVALUATION]', [
                        'current_month' => $currentMonth,
                        'total_spent'   => $totalSpent,
                        'budget_limit'  => $limit,
                        'computed_pct'  => round($percentage, 2) . '%'
                    ]);

                    if ($percentage >= 80) {
                        try {
                            $user->notify(new BudgetAlertNotification([
                                'category_name' => $transaction->category->name,
                                'percentage'    => round($percentage, 2)
                            ]));
                            Log::info('[BUDGET ALERT DISPATCHED]');
                        } catch (\Exception $e) {
                            Log::error('[BUDGET ALERT CRASHED]', ['msg' => $e->getMessage()]);
                        }
                    }
                }
            }

            // 5. Done! Return complete object to instantly push to Vue UI array list
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaction parsed and saved successfully via AI Engine',
                'data'    => $transaction
            ], 201);

        } catch (\Exception $e) {
            Log::error("System Pipeline Failure: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Server error during live execution.'], 500);
        }
    }

    /**
     * 💡 AI Financial Coach - Pichle 30 din ka Category breakdown analyze karta hai
     */
    public function getFinancialInsights()
    {
        try {
            $user = auth()->user();

            // Pichle 30 din ka data nikalna
            $expensesSummary = Transaction::where('transactions.user_id', $user->id)
                ->where('transactions.date', '>=', now()->subDays(30))
                ->join('categories', 'transactions.category_id', '=', 'categories.id')
                ->selectRaw('categories.name as category_name, SUM(transactions.amount) as total')
                ->groupBy('categories.name')
                ->get()
                ->pluck('total', 'category_name')
                ->toArray();

            if (empty($expensesSummary)) {
                return response()->json([
                    'success' => true,
                    'insights' => 'Abhi aapka pichle 30 din ka koi kharcha recorded nahi hai. Kuch entries add karein taake AI analysis shuru ho sake!'
                ]);
            }

            $summaryString = json_encode($expensesSummary);

            $prompt = "A user spent these total amounts in different categories over the past 30 days: {$summaryString}.
            Analyze this profile data and generate 2 short, highly practical budgeting tips or savings insights.
            Write strictly in Roman Urdu or simple conversational English. Keep the entire response under 60 words total.";

            $apiKey = env('GEMINI_API_KEY');

            $response = Http::withHeaders([
                'Content-Type'   => 'application/json',
                'X-goog-api-key' => $apiKey,
            ])
            ->timeout(15)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->failed()) {
                Log::error("Gemini Failure Logs: " . $response->body());
                return response()->json(['success' => false, 'message' => 'Gemini API connection failed.'], 502);
            }

            $aiResult = $response->json();

            if (isset($aiResult['candidates'][0]['content']['parts'][0]['text'])) {
                $insights = $aiResult['candidates'][0]['content']['parts'][0]['text'];

                // Frontend ko humne 'monthlyAiInsights' variable dena hai
                return response()->json([
                    'success' => true,
                    'monthlyAiInsights' => trim($insights)
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Invalid structure from AI response.'], 500);

        } catch (\Exception $e) {
            Log::error("Insights Exception: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Connection timed out or failed. Please try again.'], 500);
        }
    }

    /**
     * ⚡ Transaction Record and Smart Anomaly Detection
     */
    public function handleSubmitTransaction(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'category_id' => 'required|exists:categories,id',
            'currency' => 'string|max:10',
            'description' => 'nullable|string',
            'date' => 'required|date'
        ]);

        try {
            $user = auth()->user();
            $categoryId = $request->category_id;
            $newAmountInPkr = $request->amount;

            // 1. Baseline calculation
            $averageExpense = Transaction::where('user_id', $user->id)
                ->where('category_id', $categoryId)
                ->where('date', '>=', now()->subMonths(3))
                ->avg('amount') ?? 0;

            $isAnomaly = false;
            $anomalyWarning = null;

            // 2. Trigger rule and Gemini execution
            if ($averageExpense > 0 && $newAmountInPkr > ($averageExpense * 2)) {
                $prompt = "A user usually spends an average of {$averageExpense} PKR in this specific category channel. Today they just logged a transaction spike of {$newAmountInPkr} PKR.
                Is this an unexpected baseline deviation (anomaly)?
                Respond strictly in a clean JSON structure with these exact keys:
                'is_anomaly' (boolean true or false) and 'warning_message' (a 1-line witty caution alert in Roman Urdu or plain English warning them about this sudden trend spike). Do not output markdown code blocks, do not wrap in ```json.";

                $apiKey = env('GEMINI_API_KEY');
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("[https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=](https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=){$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $aiResult = $response->json();
                    $rawText = $aiResult['candidates'][0]['content']['parts'][0]['text'] ?? '';

                    // ⚡ Bulletproof JSON extraction: markdown clean karne ka logic
                    $cleanJson = preg_replace('/^```json\s+|```$/m', '', trim($rawText));
                    $aiData = json_decode($cleanJson, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $isAnomaly = $aiData['is_anomaly'] ?? false;
                        $anomalyWarning = $aiData['warning_message'] ?? null;
                    }
                }
            }

            // 3. Save into Database
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->category_id = $categoryId;
            $transaction->amount = $newAmountInPkr;
            $transaction->actual_amount = $request->actual_amount ?? $newAmountInPkr;
            $transaction->currency = $request->currency ?? 'PKR';
            $transaction->exchange_rate = $request->exchange_rate ?? 1.00;
            $transaction->description = $request->description;
            $transaction->date = $request->date;
            $transaction->save();

            return response()->json([
                'success' => true,
                'transaction' => $transaction,
                'is_anomaly' => $isAnomaly,
                'warning' => $anomalyWarning
            ]);

        } catch (\Exception $e) {
            Log::error("Transaction Execution Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to log transaction'], 500);
        }
    }
}
