<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\RecurringBill;
use App\Models\Transaction;
use Carbon\Carbon;

class RecurringBillController extends Controller
{
    public function markAsPaid(Request $request, $id)
    {
        $user = $request->user();
        $bill = RecurringBill::where('user_id', $user->id)->findOrFail($id);

        if ($bill->status === 'paid') {
            return response()->json(['message' => 'Bill pehle se hi paid hai.'], 400);
        }

        // DB Transaction taake dono kaam ek sath hon ya bilkul na hon
        DB::beginTransaction();
        try {
            // 1. Bill ka status paid mark karein
            $bill->update(['status' => 'paid']);

            // // 2. Automatically actual transaction logs me expense entry pass karein
            // Transaction::create([
            //     'user_id'     => auth()->id(),
            //     'category_id' => $bill->category_id,
            //     'amount'      => $bill->amount,
            //     'currency'    => $bill->currency,
            //     'description' => 'Paid Bill: ' . $bill->name,
            //     'date'        => Carbon::now()->toDateString(),
            // ]);

            DB::commit();
            return response()->json(['message' => 'Bill successfully paid and logged as expense!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Masla hua processing me: ' . $e->getMessage()], 500);
        }
    }
}
