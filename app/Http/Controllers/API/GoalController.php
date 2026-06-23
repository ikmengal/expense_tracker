<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Goal;

class GoalController extends Controller
{
    public function index(Request $request) {
        return response()->json(Goal::where('user_id', $request->user()->id)->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'currency' => 'required|string|max:3',
            'deadline' => 'nullable|date|after:today',
        ]);

        $data['user_id'] = $request->user()->id;
        $goal = Goal::create($data);

        return response()->json($goal, 201);
    }

    public function addSavings(Request $request, $id) {
        $request->validate(['amount' => 'required|numeric|min:1']);

        $goal = Goal::where('user_id', $request->user()->id)->findOrFail($id);
        $goal->saved_amount += $request->amount;

        if ($goal->saved_amount >= $goal->target_amount) {
            $goal->status = 'achieved';
        }
        $goal->save();

        return response()->json($goal);
    }

    public function destroy(Request $request, $id) {
        Goal::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return response()->json(['message' => 'Goal removed successfully']);
    }

    public function allocateToGoal(Request $request, $id)
    {
        $user = $request->user();

        // 1. Validation check
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $amount = $request->input('amount');

        // 2. Database Transaction block taake agar ek jagah fail ho to data corrupt na ho
        DB::beginTransaction();
        try {
            // 3. Check karein ke yeh goal isi user ka hai ya nahi
            $goal = Goal::where('id', $id)->where('user_id', $user->id)->first();

            if (!$goal) {
                // 🔍 Debugging response: Is se pata chalega ke backend me kya ID receive ho rahi hai
                return response()->json([
                    'status' => 'error',
                    'message' => "Goal not found! Searching for Goal ID: '{$id}' for User ID: '{$user->id}'"
                ], 404);
            }

            // 4. Goal ki current_amount ko update (plus) karein
            $goal->saved_amount += $amount;

            // Agar goal target poora ho gaya to status completed kar dein
            if ($goal->saved_amount >= $goal->target_amount) {
                $goal->status = 'completed';
            }
            $goal->save();

            // 5. User ke main transaction ledger me entries inject karein (taake balance se minus ho sake)
            // Note: Aap "Savings Transfer" ya "Goal" ke naam se ek category insert/map kar sakte hain
            $user->transactions()->create([
                'amount' => $amount,
                'currency' => $goal->currency ?? 'PKR',
                'date' => now()->toDateString(),
                'description' => "🤖 AI Smart Save: Auto-allocated to milestone target '{$goal->name}'",
                'category_id' => $goal->category_id,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully allocated {$amount} to '{$goal->name}'"
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong during allocation processing: ' . $e->getMessage()
            ], 500);
        }
    }
}
