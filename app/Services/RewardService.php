<?php

namespace App\Services;

use App\Models\RewardRecord;

class RewardService
{
    public function giveReward(int $userId, int $points, string $type, string $description): RewardRecord
    {
        return RewardRecord::create([
            'user_id'     => $userId,
            'points'      => $points,
            'reward_type' => $type,
            'description' => $description,
        ]);
    }
}