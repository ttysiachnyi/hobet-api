<?php

namespace App\Http\Controllers;

use App\Services\DailyBonusService;
use Illuminate\Http\Request;

class CasinoController extends Controller
{
    // Can be altered if needed
    private array $symbols = ['cherry', 'lemon', 'bell', 'star', 'seven'];

    public function spin(Request $request): \Illuminate\Http\JsonResponse
    {
        // Takes userId from middleware
        $userId = (int) $request->attributes->get('auth_user_id', 0);

        $bodyUserId = (int) $request->input('user_id', 0);
        if ($bodyUserId !== 0 && $bodyUserId !== $userId) {
            return response()->json(['error' => 'user_id mismatch'], 403);
        }

        $balance = (int) $request->input('balance', 1000);
        $bet     = (int) $request->input('bet', 0);

        if ($bet <= 0) {
            return response()->json(['error' => 'Bet must be positive'], 400);
        }

        $reels = [$this->randomSymbol(), $this->randomSymbol(), $this->randomSymbol()];

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
            'user_id'        => $userId,
            'bet'            => $bet,
            'reels'          => $reels,
            'win_type'       => $winType,
            'payout'         => $payout,
            'balance_before' => $balance,
            'balance_after'  => $balanceAfter,
        ]);
    }


    public function bonus(Request $request, DailyBonusService $service): \Illuminate\Http\JsonResponse
    {
        $userId = (int) $request->input('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['error' => 'user_id is required'], 400);
        }

        $bonus = $service->claim($userId);

        return response()->json(['user_id' => $userId, 'bonus' => $bonus]);
    }



    private function randomSymbol(): string
    {
        return $this->symbols[array_rand($this->symbols)];
    }
}
