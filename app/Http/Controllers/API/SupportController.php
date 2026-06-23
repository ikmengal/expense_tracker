<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Support;
use App\Models\User;

class SupportController extends Controller
{
    // User ke ticket load karna
    public function index(Request $request)
    {
        $tickets = Support::where('user_id', $request->user()->id)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json($tickets);
    }

    // Nayi ticket create karna
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:150',
            'message' => 'required|string|min:10'
        ]);

        $ticket = Support::create([
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'message' => $request->message,
            'status' => 'open', // Default open hoga
        ]);

        return response()->json([
            'message' => 'Complain logged successfully!',
            'ticket' => $ticket
        ], 201);
    }

    public function storeSupportMessage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'type' => 'required|in:problem,suggestion',
            'message' => 'required|string|min:10',
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user){
            return response()->json([
                'message' => 'No record found this email in Database.',
            ], 500);
        }

        try {
            Support::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'type' => $request->type,
                'message' => $request->message,
            ]);

            return response()->json([
                'message' => 'Feedback logged successfully into the database structure.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Database transactional exception error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
