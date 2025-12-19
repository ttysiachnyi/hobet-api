<?php

namespace App\Services;

use App\Models\Bonus;
use Illuminate\Support\Facades\DB;

class DailyBonusService
{
    public function claim(int $userId): int
    {
        return DB::transaction(function () use ($userId) {
            $row = Bonus::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $userId]);

            $now = now();
            $today = $now->toDateString();
            $yesterday = $now->copy()->subDay()->toDateString();

            if ($row->last_claimed_at && $row->last_claimed_at->toDateString() === $today) {
                abort(409, 'Bonus already claimed');
            }

            if ($row->last_claimed_at && $row->last_claimed_at->toDateString() === $yesterday) {
                $row->streak += 1;
            } else {
                $row->streak = 1; // streak = one if skipped once
            }

            $row->bonus = $this->bonusForStreak($row->streak);
            $row->status = 'received';
            $row->last_claimed_at = $now;
            $row->save();

            return $row->bonus;
        });
    }

    private function bonusForStreak(int $day): int
    {
        $linear = $day * 5; // 1->5, 2->10 ... 8->40
        if ($linear <= 40) return $linear;

        // cycle 40..35..40..35
        $cycle = [40, 35];
        return $cycle[($day - 8) % count($cycle)];
    }
}
