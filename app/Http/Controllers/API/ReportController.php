<?php

namespace App\Http\Controllers\API;

use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Setting;
use Carbon\Carbon;

class ReportController extends Controller
{
    // 1. Export Data (PDF & Excel)
    // public function export(Request $request, $format)
    // {
    //     $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date|after_or_equal:start_date',
    //     ]);

    //     $user = $request->user();
    //     $transactions = Transaction::with('category')
    //         ->where('user_id', $user->id)
    //         ->whereBetween('date', [$request->start_date, $request->end_date])
    //         ->orderBy('date', 'desc')
    //         ->get();

    //     // Settings se dynamic data fetch karna
    //     $settings = Setting::first();
    //     $siteName = $settings->site_name ?? 'Expense Tracker';

    //     // --- 📄 PDF EXPORT ---
    //     if ($format === 'pdf') {
    //         $pdf = Pdf::loadView('reports.transactions_pdf', [
    //             'transactions' => $transactions,
    //             'start_date' => $request->start_date,
    //             'end_date' => $request->end_date,
    //             'user' => $user,
    //             'site_name' => $siteName
    //         ]);

    //         return response($pdf->output(), 200, [
    //             'Content-Type' => 'application/pdf',
    //             'Content-Disposition' => 'attachment; filename="Expense-Report.pdf"',
    //         ]);
    //     }

    //     // --- 📊 EXCEL EXPORT ---
    //     if ($format === 'excel') {
    //         $fileContents = Excel::raw(
    //             new TransactionsExport($transactions, $siteName, $request->start_date, $request->end_date, $user),
    //             ExcelFormat::XLSX
    //         );

    //         return response($fileContents, 200, [
    //             'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    //             'Content-Disposition' => 'attachment; filename="Expense-Report.xlsx"',
    //         ]);
    //     }

    //     return response()->json(['message' => 'Invalid Format'], 400);
    // }

    // public function export(Request $request, $format)
    // {
    //     $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date|after_or_equal:start_date',
    //     ]);

    //     $user = $request->user();

    //     // 1. Transactions fetch karna (Original Query)
    //     $transactions = Transaction::with('category')
    //         ->where('user_id', $user->id)
    //         ->whereBetween('date', [$request->start_date, $request->end_date])
    //         ->orderBy('date', 'desc')
    //         ->get();

    //     // Settings se dynamic data fetch karna
    //     $settings = Setting::first();
    //     $siteName = $settings->site_name ?? 'Expense Tracker';

    //     // --- 🤖 AI AUDIT SUMMARY GENERATOR (ADDED FOR SMART REPORT) ---
    //     // User ki income aur expenses ka sum isi date range ke mutabiq nikalna
    //     $totalIncome = $transactions->where('type', 'income')->sum('amount');
    //     $totalExpense = $transactions->where('type', 'expense')->sum('amount');
    //     $netBalance = $totalIncome - $totalExpense;
    //     $savingsRate = $totalIncome > 0 ? round(($netBalance / $totalIncome) * 100, 1) : 0;

    //     // Default statement
    //     $aiAudit = "This automated audit report provides a clean breakdown of your financial flows from {$request->start_date} to {$request->end_date}. ";

    //     if ($savingsRate >= 20) {
    //         $aiAudit .= "Excellent wealth health! You have successfully saved {$savingsRate}% of your total earnings in this period, pacing well above standard structural safety milestones.";
    //     } elseif ($savingsRate > 0 && $savingsRate < 20) {
    //         $aiAudit .= "Stable, but close monitoring suggested. Your current savings velocity stands at {$savingsRate}%. Minor adjustments in non-essential category buckets can reinforce your milestone targets.";
    //     } else {
    //         $aiAudit .= "⚠️ Critical Warning: Your expenses have breached or matched your active revenue pool for this period. Outflows require immediate optimization to preserve target commitments.";
    //     }

    //     // Top Expense Category dhoondna isi collection se
    //     $topCategoryData = $transactions->where('type', 'expense')
    //         ->groupBy('category_id')
    //         ->map(function ($row) {
    //             return [
    //                 'total' => $row->sum('amount'),
    //                 'name' => $row->first()->category->name ?? 'Uncategorized'
    //             ];
    //         })->sortByDesc('total')->first();

    //     if ($topCategoryData && $topCategoryData['total'] > 0) {
    //         $aiAudit .= " Visual tracking detects significant weight allocated towards '{$topCategoryData['name']}'. Regular pacing checks are advised.";
    //     }
    //     // --- 🤖 END AI SMART LOGIC ---


    //     // --- 📄 PDF EXPORT ---
    //     if ($format === 'pdf') {
    //         // ✨ FIXED: 'ai_audit' variable ko array ke andar pass kar diya
    //         $pdf = Pdf::loadView('reports.transactions_pdf', [
    //             'transactions' => $transactions,
    //             'start_date' => $request->start_date,
    //             'end_date' => $request->end_date,
    //             'user' => $user,
    //             'site_name' => $siteName,
    //             'ai_audit' => $aiAudit // 👈 Yeh variable pass kiya hy
    //         ]);

    //         return response($pdf->output(), 200, [
    //             'Content-Type' => 'application/pdf',
    //             'Content-Disposition' => 'attachment; filename="Expense-Report.pdf"',
    //         ]);
    //     }

    //     // --- 📊 EXCEL EXPORT ---
    //     if ($format === 'excel') {
    //         $fileContents = Excel::raw(
    //             new TransactionsExport($transactions, $siteName, $request->start_date, $request->end_date, $user),
    //             ExcelFormat::XLSX
    //         );

    //         return response($fileContents, 200, [
    //             'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    //             'Content-Disposition' => 'attachment; filename="Expense-Report.xlsx"',
    //         ]);
    //     }

    //     return response()->json(['message' => 'Invalid Format'], 400);
    // }

    // public function export(Request $request, $format)
    // {
    //     $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date|after_or_equal:start_date',
    //     ]);

    //     $user = $request->user();

    //     // 1. Aapki original single query jo exact frontend dates ka record get karegi
    //     $transactions = Transaction::with('category')
    //         ->where('user_id', $user->id)
    //         ->whereBetween('date', [$request->start_date, $request->end_date])
    //         ->orderBy('date', 'desc')
    //         ->get();

    //     // Settings se dynamic data fetch karna
    //     $settings = Setting::first();
    //     $siteName = $settings->site_name ?? 'SpendSense';

    //     // --- 🤖 AI AUDIT SUMMARY GENERATOR (Using the fetched collection directly) ---
    //     // Alag se DB query chalane ke bajaye pehle se fetched $transactions collection par filter lagaya
    //     $totalIncome = $transactions->filter(fn($t) => ($t->category?->type ?? $t->type) === 'income')->sum('amount');
    //     $totalExpense = $transactions->filter(fn($t) => ($t->category?->type ?? $t->type) === 'expense')->sum('amount');

    //     $netBalance = $totalIncome - $totalExpense;
    //     $savingsRate = $totalIncome > 0 ? round(($netBalance / $totalIncome) * 100, 1) : 0;

    //     // Readable dynamic dates string for AI text context
    //     $formattedStart = date('M d, Y', strtotime($request->start_date));
    //     $formattedEnd = date('M d, Y', strtotime($request->end_date));

    //     $aiAudit = "This automated audit report provides a clean breakdown of your financial flows from {$formattedStart} to {$formattedEnd}. ";

    //     if ($savingsRate >= 20) {
    //         $aiAudit .= "Excellent wealth health! You have successfully saved {$savingsRate}% of your total earnings in this period, pacing well above standard structural safety milestones.";
    //     } elseif ($savingsRate > 0 && $savingsRate < 20) {
    //         $aiAudit .= "Stable, but close monitoring suggested. Your current savings velocity stands at {$savingsRate}%. Minor adjustments in non-essential category buckets can reinforce your milestone targets.";
    //     } else {
    //         $aiAudit .= "⚠️ Critical Warning: Your expenses have breached or matched your active revenue pool for this period. Outflows require immediate optimization to preserve target commitments.";
    //     }

    //     // Top Expense Category dynamically from the same collection
    //     $topCategoryData = $transactions->where('type', 'expense')
    //         ->groupBy('category_id')
    //         ->map(function ($row) {
    //             return [
    //                 'total' => $row->sum('amount'),
    //                 'name' => $row->first()->category->name ?? 'Uncategorized'
    //             ];
    //         })->sortByDesc('total')->first();

    //     if ($topCategoryData && $topCategoryData['total'] > 0) {
    //         $aiAudit .= " Visual tracking detects significant weight allocated towards '{$topCategoryData['name']}'. Regular pacing checks are advised.";
    //     }
    //     // --- 🤖 END AI SMART LOGIC ---


    //     // --- 📄 PDF EXPORT ---
    //     if ($format === 'pdf') {
    //         $pdf = Pdf::loadView('reports.transactions_pdf', [
    //             'transactions' => $transactions,
    //             'start_date' => $request->start_date,
    //             'end_date' => $request->end_date,
    //             'user' => $user,
    //             'site_name' => $siteName,
    //             'ai_audit' => $aiAudit,
    //             'setting' => $settings,
    //         ]);

    //         return response($pdf->output(), 200, [
    //             'Content-Type' => 'application/pdf',
    //             'Content-Disposition' => 'attachment; filename="Expense-Report.pdf"',
    //         ]);
    //     }

    //     // --- 📊 EXCEL EXPORT ---
    //     if ($format === 'excel') {
    //         $fileContents = Excel::raw(
    //             new TransactionsExport($transactions, $siteName, $request->start_date, $request->end_date, $user, $aiAudit), // 👈 Aakhir me $aiAudit pass krden
    //             \Maatwebsite\Excel\Excel::XLSX
    //         );

    //         return response($fileContents, 200, [
    //             'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    //             'Content-Disposition' => 'attachment; filename="Expense-Report.xlsx"',
    //         ]);
    //     }

    //     return response()->json(['message' => 'Invalid Format'], 400);
    // }

    public function export(Request $request, $format)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $user = $request->user();

        $transactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->orderBy('date', 'desc')
            ->get();

        $settings = Setting::first();
        $siteName = $settings->site_name ?? 'SpendSense';

        $logoPath = public_path('images/logo.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $mimeType = mime_content_type($logoPath);
            $logoBase64 = "data:{$mimeType};base64,{$logoData}";
        }

        $totalIncome = $transactions->filter(fn($t) => ($t->category?->type ?? $t->type) === 'income')->sum('amount');
        $totalExpense = $transactions->filter(fn($t) => ($t->category?->type ?? $t->type) === 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? round(($netBalance / $totalIncome) * 100, 1) : 0;

        $formattedStart = date('M d, Y', strtotime($request->start_date));
        $formattedEnd = date('M d, Y', strtotime($request->end_date));

        $aiAudit = "This automated audit report provides a clean breakdown of your financial flows from {$formattedStart} to {$formattedEnd}. ";

        if ($savingsRate >= 20) {
            $aiAudit .= "Excellent wealth health! You have successfully saved {$savingsRate}% of your total earnings in this period, pacing well above standard structural safety milestones.";
        } elseif ($savingsRate > 0 && $savingsRate < 20) {
            $aiAudit .= "Stable, but close monitoring suggested. Your current savings velocity stands at {$savingsRate}%. Minor adjustments in non-essential category buckets can reinforce your milestone targets.";
        } else {
            $aiAudit .= "⚠️ Critical Warning: Your expenses have breached or matched your active revenue pool for this period. Outflows require immediate optimization to preserve target commitments.";
        }

        $topCategoryData = $transactions->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($row) {
                return [
                    'total' => $row->sum('amount'),
                    'name' => $row->first()->category->name ?? 'Uncategorized',
                ];
            })->sortByDesc('total')->first();

        if ($topCategoryData && $topCategoryData['total'] > 0) {
            $aiAudit .= " Visual tracking detects significant weight allocated towards '{$topCategoryData['name']}'. Regular pacing checks are advised.";
        }

        if ($format === 'pdf') {
            $settings = Setting::first();

            $logoPath = public_path(
                str_replace(url('/'), '', $settings->site_logo)
            );
            $pdf = Pdf::loadView('reports.transactions_pdf', [
                'transactions' => $transactions,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'user' => $user,
                'site_name' => $siteName,
                'ai_audit' => $aiAudit,
                'setting' => $settings,
                'logo_base64' => $logoPath,
            ]);

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Expense-Report.pdf"',
            ]);
        }

        if ($format === 'excel') {
            $fileContents = Excel::raw(
                new TransactionsExport($transactions, $siteName, $request->start_date, $request->end_date, $user, $aiAudit, $logoPath),
                \Maatwebsite\Excel\Excel::XLSX
            );

            return response($fileContents, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="Expense-Report.xlsx"',
            ]);
        }

        return response()->json(['message' => 'Invalid Format'], 400);
    }

    // // 2. Budget Alerts & Status Fetch Engine (Synced with Base Currency)
    // public function getBudgetAlerts(Request $request)
    // {
    //     $user = $request->user();
    //     $currentMonth = date('Y-m');

    //     // Har category ka total expense aur uski limit nikalna
    //     $budgetStatus = Category::where('user_id', $user->id)
    //         ->where('type', 'expense')
    //         ->where('budget_limit', '>', 0)
    //         ->get()
    //         ->map(function ($category) use ($currentMonth, $user) {
    //             // Aggregation hamesha Base PKR Amount par hogi uniform tracking ke liye
    //             $totalSpent = Transaction::where('user_id', $user->id)
    //                 ->where('category_id', $category->id)
    //                 ->where('date', 'like', $currentMonth . '%')
    //                 ->sum('amount');

    //             $percentage = $category->budget_limit > 0 ? ($totalSpent / $category->budget_limit) * 100 : 0;

    //             return [
    //                 'category_id' => $category->id,
    //                 'category_name' => $category->name,
    //                 'category_icon' => $category->icon,
    //                 'budget_limit' => $category->budget_limit,
    //                 'total_spent' => $totalSpent,
    //                 'percentage' => round($percentage, 2),
    //                 'is_alert' => $percentage >= 80
    //             ];
    //         });

    //     return response()->json($budgetStatus);
    // }

    public function getBudgetAlerts(Request $request)
    {
        $user = $request->user();

        // 📅 Target Month processing from query parameters
        $selectedMonth = str_pad($request->get('month', Carbon::now()->month), 2, '0', STR_PAD_LEFT);
        $selectedYear = $request->get('year', Carbon::now()->year);
        $targetPeriodString = $selectedYear . '-' . $selectedMonth;

        $budgetStatus = Category::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('budget_limit', '>', 0)
            ->get()
            ->map(function ($category) use ($targetPeriodString, $user) {
                $totalSpent = Transaction::where('user_id', $user->id)
                    ->where('category_id', $category->id)
                    ->where('date', 'like', $targetPeriodString . '%')
                    ->sum('amount');

                $percentage = $category->budget_limit > 0 ? ($totalSpent / $category->budget_limit) * 100 : 0;

                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_icon' => $category->icon,
                    'budget_limit' => $category->budget_limit,
                    'total_spent' => $totalSpent,
                    'percentage' => round($percentage, 2),
                    'is_alert' => $percentage >= 80
                ];
            });

        return response()->json($budgetStatus);
    }
}
