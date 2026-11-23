<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rewards = $user->rewardRecords()
            ->orderByDesc('created_at')
            ->paginate(10);

        $totalPoints = $user->total_points;

        return view('rewards.index', compact('rewards', 'totalPoints'));
    }
}