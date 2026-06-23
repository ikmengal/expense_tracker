<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body>
        <table border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td colspan="3" style="font-size: 18px; font-weight: bold; color: #1e3a8a;">
                    {{ strtoupper($site_name ?? 'SPENDSENSE') }}
                </td>
                <td></td>
                <td style="font-size: 13px; font-weight: bold; text-align: right; color: #334155;">
                    BALANCE SHEET REPORT
                </td>
            </tr>
            <tr>
                <td colspan="3" style="font-size: 10px; color: #64748b; font-style: italic;">
                    Reliable Financial Ecosystem Intelligence
                </td>
                <td></td>
                <td style="font-size: 9px; color: #94a3b8; text-align: right; font-style: italic;">
                    As of: {{ date('d M, Y h:i A') }}
                </td>
            </tr>
            <tr><td colspan="5" style="height: 20px;"></td></tr>
            <tr>
                <td style="font-weight: bold; color: #475569; font-size: 10px;">MANAGER/USER:</td>
                <td colspan="2" style="font-size: 11px; font-weight: bold; color: #0f172a;">{{ $user->name }}</td>
                <td style="font-weight: bold; color: #475569; font-size: 10px; text-align: right;">STATEMENT PERIOD:</td>
                <td style="color: #1e3a8a; font-weight: bold; font-size: 11px; text-align: right;">
                    {{ date('M Y', strtotime($start_date)) }} - {{ date('M Y', strtotime($end_date)) }}
                </td>
            </tr>
            <tr>
                <td style="color: #475569; font-size: 10px;">EMAIL INSTANCE:</td>
                <td colspan="2" style="color: #334155; font-size: 10px;">{{ $user->email }}</td>
                <td></td>
                <td style="color: #94a3b8; font-size: 9px; text-align: right; font-style: italic;">Currency: PKR (Rs.)</td>
            </tr>
            <tr><td colspan="5" style="height: 20px;"></td></tr>
            @if(!empty($ai_audit))
                <tr>
                    <td colspan="5" style="background-color: #f8fafc; font-weight: bold; color: #0f172a; font-size: 11px; border-top: 2px solid #334155;">
                        📝 {{ strtoupper($site_name ?? 'SpendSense') }} AI AUDIT SUMMARY NOTES
                    </td>
                </tr>
                <tr>
                    <td colspan="5" style="background-color: #f8fafc; font-style: italic; color: #475569; font-size: 10px; border-bottom: 2px solid #334155;">
                        "{{ $ai_audit }}"
                    </td>
                </tr>
                <tr><td colspan="5" style="height: 20px;"></td></tr>
            @endif
            @php
                $totalIncome = $transactions->filter(fn($t) => ($t->category?->type ?? $t->type) === 'income')->sum('amount');
                $totalExpense = $transactions->filter(fn($t) => ($t->category?->type ?? $t->type) === 'expense')->sum('amount');
                $netBalance = $totalIncome - $totalExpense;
            @endphp
            <tr>
                <td colspan="2" style="background-color: #fdfcfa; color: #78350f; font-weight: bold; font-size: 10px; text-align: center; border: 1px solid #fef3c7;">TOTAL ASSETS / INCOME</td>
                <td></td>
                <td colspan="2" style="background-color: #fdfcfa; color: #78350f; font-weight: bold; font-size: 10px; text-align: center; border: 1px solid #fef3c7;">TOTAL LIABILITIES / EXPENSE</td>
            </tr>
            <tr>
                <td colspan="2" style="background-color: #fdfcfa; color: #92400e; font-weight: bold; font-size: 14px; text-align: center; border: 1px solid #fef3c7;">Rs. {{ number_format($totalIncome, 2) }}</td>
                <td></td>
                <td colspan="2" style="background-color: #fdfcfa; color: #92400e; font-weight: bold; font-size: 14px; text-align: center; border: 1px solid #fef3c7;">Rs. {{ number_format($totalExpense, 2) }}</td>
            </tr>
            <tr><td colspan="5" style="height: 10px;"></td></tr>
            <tr>
                <td colspan="2" style="background-color: #334155; color: #ffffff; font-weight: bold; font-size: 11px; border: 1px solid #334155;">TOTAL LIABILITIES AND EQUITY (NET)</td>
                <td style="background-color: #334155; color: #ffffff; font-weight: bold; font-size: 11px; border: 1px solid #334155; text-align: right;">Rs.</td>
                <td colspan="2" style="background-color: #334155; color: #ffffff; font-weight: bold; font-size: 12px; border: 1px solid #334155; text-align: right;">
                    {{ number_format($netBalance, 2) }}
                </td>
            </tr>
            <tr><td colspan="5" style="height: 25px;"></td></tr>
            <tr>
                <td colspan="5" style="font-weight: bold; font-size: 12px; color: #1e293b; text-align: left; padding-bottom: 5px;">
                    ASSETS & INFLOW LEDGER RECORDS
                </td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #475569; color: #ffffff; font-size: 10px; border: 1px solid #475569; text-align: left;">DATE</td>
                <td style="font-weight: bold; background-color: #475569; color: #ffffff; font-size: 10px; border: 1px solid #475569; text-align: left;">CURRENT ASSETS / DESCRIPTION</td>
                <td style="font-weight: bold; background-color: #475569; color: #ffffff; font-size: 10px; border: 1px solid #475569; text-align: left;">CATEGORY ELEMENT</td>
                <td style="font-weight: bold; background-color: #475569; color: #ffffff; font-size: 10px; border: 1px solid #475569; text-align: right;">AMOUNT (LOCAL)</td>
                <td style="font-weight: bold; background-color: #475569; color: #ffffff; font-size: 10px; border: 1px solid #475569; text-align: right;">REMAINING AMOUNT</td>
            </tr>
            @forelse($transactions as $tx)
                @php
                    $txType = $tx->category?->type ?? $tx->type ?? 'expense';
                    $currencySymbol = match($tx->currency) {
                        'USD' => '$ ', 'EUR' => '€ ', 'AED' => 'د.إ ', 'GBP' => '£ ', default => 'Rs. ',
                    };
                    $localAmount = $tx->actual_amount ?? $tx->amount;
                    $sign = ($txType === 'income') ? '+' : '-';
                @endphp
                <tr>
                    <td style="border: 1px solid #e2e8f0; font-size: 10px; color: #334155;">
                        {{ date('d M, Y', strtotime($tx->date)) }}
                    </td>
                    <td style="border: 1px solid #e2e8f0; font-size: 10px; font-weight: bold; color: #0f172a;">
                        {{ $tx->description ?? 'General Item Allocation' }}
                    </td>
                    <td style="border: 1px solid #e2e8f0; font-size: 10px; color: #475569;">
                        {{ $tx->category?->name ?? 'Uncategorized Asset' }}
                    </td>
                    <td style="border: 1px solid #e2e8f0; text-align: right; font-size: 10px; font-weight: bold; color: #1e293b;">
                        {{ $sign }}{{ $currencySymbol }}{{ number_format($localAmount, 2) }}
                    </td>
                    <td style="border: 1px solid #e2e8f0; text-align: right; color: #0f172a; font-weight: bold; font-size: 10px;">
                        Rs. {{ number_format($tx->amount, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; font-size: 11px; font-style: italic; border: 1px solid #e2e8f0; padding: 10px;">
                        No assets ledger transactions detected within this targeted reporting window framework.
                    </td>
                </tr>
            @endforelse
            <tr><td colspan="5" style="height: 20px;"></td></tr>
            <tr>
                <td colspan="5" style="font-style: italic; color: #94a3b8; font-size: 9px; text-align: center;">
                    * This comprehensive document simplifies the creation, calculation, and micro-analysis of your platform balance sheet parameters.
                </td>
            </tr>
        </table>
    </body>
</html>
