<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CurrencyService; // Aapka currency service wrapper
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function getDashboardData(Request $request)
    {
        $user = $request->user();
        // Target currency pakrein, default PKR
        $targetCurrency = $request->query('currency', $user->default_currency ?? 'PKR');

        // Current Year ki saari transactions load karein with categories
        $transactions = $user->transactions()
            ->with('category')
            ->whereYear('date', Carbon::now()->year)
            ->get();

        // --- 1. Monthly Income vs Expense Structure ---
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyIncome = array_fill(0, 12, 0);
        $monthlyExpense = array_fill(0, 12, 0);

        // --- 2. Category Breakdown Structure ---
        $categoryTotals = [];

        foreach ($transactions as $tx) {
            if (!$tx->category) continue;

            // Base currency assumed to be PKR in DB, converting to requested currency
            // Agar aap database mein transaction ki apni currency se convert kar rahe hain to 'PKR' ki jagah $tx->currency use karein
            $convertedAmount = CurrencyService::convert($tx->amount, 'PKR', $targetCurrency);
            $convertedAmount = round($convertedAmount, 2);

            $monthIndex = Carbon::parse($tx->date)->month - 1; // 0-indexed month

            if ($tx->category->type === 'income') {
                $monthlyIncome[$monthIndex] += $convertedAmount;
            } else {
                $monthlyExpense[$monthIndex] += $convertedAmount;

                // Category-wise Expense breakdown logic
                $catName = $tx->category->name;
                $catIcon = $tx->category->icon ?? '📁';
                $key = $catIcon . ' ' . $catName;

                if (!isset($categoryTotals[$key])) {
                    $categoryTotals[$key] = 0;
                }
                $categoryTotals[$key] += $convertedAmount;
            }
        }

        // Format category breakdown for Doughnut/Pie Chart
        $categoryLabels = array_keys($categoryTotals);
        $categoryData = array_values($categoryTotals);

        return response()->json([
            'currency' => $targetCurrency,
            'monthly' => [
                'labels' => $months,
                'income' => $monthlyIncome,
                'expense' => $monthlyExpense
            ],
            'categories' => [
                'labels' => $categoryLabels,
                'data' => $categoryData
            ]
        ], 200);
    }
}
