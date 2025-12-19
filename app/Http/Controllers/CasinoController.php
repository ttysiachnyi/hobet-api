<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CasinoController extends Controller
{
    // Can be altered if needed
    private array $symbols = ['🍒', '🍋', '🔔', '⭐', '7️⃣'];

    public function spin(Request $request)
    {
        // Exploit it! No necessary checks!
        $userId  = (int) $request->input('user_id', 0);      // No purpose rn, perhaps could be used later
        $balance = (int) $request->input('balance', 1000);   // remove it later
        $bet     = (int) $request->input('bet', 0);

        // Simple juxtaposition
        if ($bet <= 0) {
            return response()->json(['error' => 'Bet must be positive'], 400);
        }

        // Spin
        $reels = [
            $this->randomSymbol(),
            $this->randomSymbol(),
            $this->randomSymbol(),
        ];

        // 3 identical slots - x10, 2 identical ones - x2, otherwise 0
        $payout = 0;
        $winType = 'none';

        if ($reels[0] === $reels[1] && $reels[1] === $reels[2]) {
            $payout = $bet * 10;
            $winType = 'tenfold';
        } elseif ($reels[0] === $reels[1] || $reels[0] === $reels[2] || $reels[1] === $reels[2]) {
            $payout = $bet * 2;
            $winType = 'twofold';
        }

        $balanceAfter = $balance - $bet + $payout;

        return response()->json([
            'user_id'       => $userId,
            'bet'           => $bet,
            'reels'         => $reels,
            'win_type'      => $winType,
            'payout'        => $payout,
            'balance_before'=> $balance,
            'balance_after' => $balanceAfter,
        ]);
    }

    private function randomSymbol(): string
    {
        return $this->symbols[array_rand($this->symbols)];
    }
}
