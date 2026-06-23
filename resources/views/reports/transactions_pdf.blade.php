<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Financial Report</title>
        <style>
            @page { margin: 40px 45px; }
            body { font-family: 'Helvetica', Arial, sans-serif; color: #1e293b; font-size: 12px; line-height: 1.5; }

            /* 🏛️ Layout Helper Matrix */
            .w-100 { width: 100%; }
            .clearfix::after { content: ""; clear: both; display: table; }
            .text-right { text-align: right; }
            .text-muted { color: #64748b; font-size: 11px; }

            /* 🏷️ Header Design Layout */
            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
            .logo-img { max-height: 45px; width: auto; display: inline-block; vertical-align: middle; margin-right: 12px; }
            .site-name { font-size: 22px; font-weight: 800; color: #4f46e5; vertical-align: middle; letter-spacing: -0.5px; }
            .report-title { font-size: 18px; font-weight: 900; color: #0f172a; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
            .report-date { font-size: 11px; color: #64748b; margin-top: 4px; font-family: monospace; }

            /* 👥 Meta Information Section */
            .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; background-color: #f8fafc; border-radius: 8px; padding: 12px 18px; border: 1px solid #f1f5f9; }
            .meta-label { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 800; tracking: 0.5px; }
            .meta-value { font-size: 13px; color: #0f172a; font-weight: 700; margin-top: 1px; }

            /* 🤖 Embedded Premium AI Audit Summary Box */
            .ai-audit-box { background-color: #fafafa; border-left: 4px solid #4f46e5; padding: 14px 16px; margin-bottom: 25px; border-radius: 4px; border-top: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; }
            .ai-title { margin-top: 0; margin-bottom: 5px; color: #1e1b4b; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.3px; }
            .ai-text { margin: 0; color: #334155; font-size: 11.5px; line-height: 1.5; font-style: italic; }

            /* 📊 Metrics Summary Cards Grid Table Layer */
            .cards-table { width: 100%; border-collapse: separate; border-spacing: 12px 0; margin-left: -12px; margin-right: -12px; margin-bottom: 30px; }
            .card-td { width: 33.33%; padding: 14px; border-radius: 10px; }
            .bg-income { background-color: #f0fdf4; border: 1px solid #bbf7d0; }
            .bg-expense { background-color: #fef2f2; border: 1px solid #fecaca; }
            .bg-balance { background-color: #eef2ff; border: 1px solid #e0e7ff; }
            .card-title { font-size: 10px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; }
            .text-income { color: #166534; }
            .text-expense { color: #991b1b; }
            .text-balance { color: #3730a3; }
            .card-amount { font-size: 18px; font-weight: 800; margin-top: 4px; }

            /* 📑 Transaction History Ledger Table */
            .table-title { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.3px; }
            .data-table { width: 100%; border-collapse: collapse; text-align: left; }
            .data-table th { background-color: #f8fafc; color: #475569; font-weight: 800; font-size: 10px; text-transform: uppercase; padding: 10px 12px; border-bottom: 2px solid #cbd5e1; letter-spacing: 0.5px; }
            .data-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; color: #334155; vertical-align: middle; }
            .td-income { color: #15803d; font-weight: 700; }
            .td-expense { color: #b91c1c; font-weight: 700; }

            /* 🖨️ System Stamp Footer */
            .system-footer { margin-top: 45px; text-align: center; border-top: 1px dashed #cbd5e1; padding-top: 12px; font-style: italic; color: #94a3b8; font-size: 10px; }
        </style>
    </head>
    <body>
        @php
            // Settings Database Table Engine Parser
            $settings = \DB::table('settings')->first();
            $siteName = $settings->site_name ?? 'SpendSense';

            // Uploaded path context map
            $logoPath = ($settings && $settings->site_logo) ? public_path('storage/' . $settings->site_logo) : null;
            $base64Logo = null;

            if ($logoPath && file_exists($logoPath)) {
                try {
                    // 🚀 Mime type direct file content se read karein taake exact stream standard generate ho
                    $mimeType = mime_content_type($logoPath);
                    $data = file_get_contents($logoPath);
                    $base64Logo = 'data:' . $mimeType . ';base64,' . base64_encode($data);
                } catch (\Exception $e) {
                    $base64Logo = null; // Backup state if format breaks
                }
            }
        @endphp

        <table class="header-table">
            <tr>
                <td style="border: none; padding: 0; vertical-align: middle;">
                    @if($logo_base64)
                        <img src="{{ $logo_base64 }}" class="logo-img" alt="Logo">
                    @else
                        <span class="site-name">{{ $siteName }}</span>
                    @endif
                </td>
                <td class="text-right" style="border: none; padding: 0; vertical-align: middle;">
                    <h1 class="report-title">Statement of Account</h1>
                    <div class="report-date">Generated: {{ date('d M, Y h:i A') }}</div>
                </td>
            </tr>
        </table>

        <table class="meta-table">
            <tr>
                <td style="border: none; padding: 0; width: 50%;">
                    <div class="meta-label">Prepared For:</div>
                    <div class="meta-value">{{ $user->name }}</div>
                    <div style="font-size: 11px; color: #64748b; font-weight: 500; margin-top: 1px;">{{ $user->email }}</div>
                </td>
                <td class="text-right" style="border: none; padding: 0; width: 50%; vertical-align: top;">
                    <div class="meta-label">Statement Period:</div>
                    <div class="meta-value" style="color: #4f46e5;">
                        {{ date('d M, Y', strtotime($start_date)) }} - {{ date('d M, Y', strtotime($end_date)) }}
                    </div>
                </td>
            </tr>
        </table>

        @if(!empty($ai_audit))
            <div class="ai-audit-box">
                <h3 class="ai-title">🤖 {{ $siteName }} AI Automated Financial Audit Summary</h3>
                <p class="ai-text">
                    "{{ $ai_audit }}"
                </p>
            </div>
        @endif

        @php
            // Core ledger sums validation metrics computation
            $totalIncome = $transactions->filter(fn($t) => ($t->category?->type ?? $t->type) === 'income')->sum('amount');
            $totalExpense = $transactions->filter(fn($t) => ($t->category?->type ?? $t->type) === 'expense')->sum('amount');
            $netBalance = $totalIncome - $totalExpense;
        @endphp

        <table class="cards-table">
            <tr>
                <td class="card-td bg-income">
                    <div class="card-title text-income">Total Income (Base)</div>
                    <div class="card-amount text-income">Rs. {{ number_format($totalIncome, 2) }}</div>
                </td>
                <td class="card-td bg-expense">
                    <div class="card-title text-expense">Total Expense (Base)</div>
                    <div class="card-amount text-expense">Rs. {{ number_format($totalExpense, 2) }}</div>
                </td>
                <td class="card-td bg-balance">
                    <div class="card-title text-balance">Net Balance (PKR)</div>
                    <div class="card-amount text-balance" style="color: {{ $netBalance >= 0 ? '#3730a3' : '#b91c1c' }}">
                        Rs. {{ number_format($netBalance, 2) }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="table-title">Transaction History Log</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Date</th>
                    <th style="width: 36%;">Description</th>
                    <th style="width: 18%;">Category</th>
                    <th style="width: 17%; text-align: right;">Amount (Local)</th>
                    <th style="width: 15%; text-align: right;">Base PKR</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    @php
                        $txType = $tx->category?->type ?? $tx->type ?? 'expense';
                        // Currency Symbol Mapper Node Parser
                        $currencySymbol = match($tx->currency) {
                            'USD' => '$',
                            'EUR' => '€',
                            'AED' => 'د.إ ',
                            'GBP' => '£',
                            default => 'Rs. ',
                        };
                        $localAmount = $tx->actual_amount ?? $tx->amount;
                    @endphp
                    <tr>
                        <td>{{ date('d M, Y', strtotime($tx->date)) }}</td>
                        <td style="font-weight: 500;">{{ $tx->description ?? 'No Description' }}</td>
                        <td>{{ $tx->category?->name ?? 'Uncategorized' }}</td>
                        <td class="text-right {{ $txType === 'income' ? 'td-income' : 'td-expense' }}">
                            {{ $txType === 'income' ? '+' : '-' }}{{ $currencySymbol }}{{ number_format($localAmount, 2) }}
                        </td>
                        <td class="text-right text-muted" style="font-family: monospace;">
                            Rs. {{ number_format($tx->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 40px; font-weight: 500;">
                            No transactions discovered within this selected reporting timeline profile.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="system-footer">
            * This document constitutes an automated diagnostic summary processed exclusively by the {{ strtoupper($siteName) }} technology ecosystem. No manual authorizations required.
        </div>
    </body>
</html>
