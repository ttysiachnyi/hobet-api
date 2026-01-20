<?php

namespace App\Http\Controllers;

use App\Services\DailyBonusService;
use Illuminate\Http\Request;

class CasinoController extends Controller
{
    private array $symbols = ['cherry', 'lemon', 'bell', 'star', 'seven'];

    public function spin(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId  = (int) $request->attributes->get('auth_user_id', 0);
        $balance = (int) $request->attributes->get('auth_balance', 0);

        if ($userId <= 0) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Optional: if body user_id exists, enforce match
        $bodyUserId = (int) $request->input('user_id', 0);
        if ($bodyUserId !== 0 && $bodyUserId !== $userId) {
            return response()->json(['error' => 'user_id mismatch'], 403);
        }

        $bet = (int) $request->input('bet', 0);
        if ($bet <= 0) {
            return response()->json(['error' => 'Bet must be positive'], 400);
        }

        if ($bet > $balance) {
            return response()->json(['error' => 'Insufficient balance'], 400);
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

        // NEW: return rotated token containing updated balance
        $newToken = $this->issueToken($userId, $balanceAfter);

        return response()->json([
            'user_id'        => $userId,
            'bet'            => $bet,
            'reels'          => $reels,
            'win_type'       => $winType,
            'payout'         => $payout,
            'balance_before' => $balance,
            'balance_after'  => $balanceAfter,
            'token'          => $newToken,
        ]);
    }

    public function bonus(Request $request, DailyBonusService $service): \Illuminate\Http\JsonResponse
    {
        $userId  = (int) $request->attributes->get('auth_user_id', 0);
        $balance = (int) $request->attributes->get('auth_balance', 0);

        if ($userId <= 0) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $bonus = $service->claim($userId);

        $balanceAfter = $balance + $bonus;

        $newToken = $this->issueToken($userId, $balanceAfter);

        return response()->json([
            'user_id'       => $userId,
            'bonus'         => $bonus,
            'balance_after' => $balanceAfter,
            'token'         => $newToken,
        ]);
    }

    private function randomSymbol(): string
    {
        return $this->symbols[array_rand($this->symbols)];
    }

    private function b64urlEncode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function issueToken(int $userId, int $balance, int $ttlSeconds = 3600): string
    {
        $now = time();

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'user_id' => $userId,
            'balance' => $balance,
            'iat'     => $now,
            'exp'     => $now + $ttlSeconds,
        ];

        $h = $this->b64urlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $p = $this->b64urlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signingInput = $h . '.' . $p;

        $secret = config('minijwt.secret', 'casino');
        $sig = $this->b64urlEncode(hash_hmac('sha256', $signingInput, $secret, true));

        return $signingInput . '.' . $sig;
    }
}
