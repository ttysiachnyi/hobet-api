<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MiniJwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken(); // takes "Bearer xxx"
        if (!$token) {
            return response()->json(['error' => 'Missing bearer token'], 401);
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return response()->json(['error' => 'Bad token format'], 401);
        }

        [$h, $p, $sig] = $parts;

        $secret = config('minijwt.secret', 'casino');
        $signingInput = $h . '.' . $p;

        // base64url(HMAC-SHA256)
        $expectedSig = $this->b64urlEncode(hash_hmac('sha256', $signingInput, $secret, true));

        if (!hash_equals($expectedSig, $sig)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payloadJson = $this->b64urlDecode($p);
        if ($payloadJson === false) {
            return response()->json(['error' => 'Bad payload encoding'], 401);
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return response()->json(['error' => 'Bad payload JSON'], 401);
        }

        $exp = (int)($payload['exp'] ?? 0);
        if ($exp <= 0 || time() >= $exp) {
            return response()->json(['error' => 'Token expired'], 401);
        }

        $userId = (int)($payload['user_id'] ?? 0);
        if ($userId <= 0) {
            return response()->json(['error' => 'user_id missing in token'], 401);
        }

        $request->attributes->set('auth_user_id', $userId);

        return $next($request);
    }

    private function b64urlEncode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $s)
    {
        $s = strtr($s, '-_', '+/');
        $pad = strlen($s) % 4;
        if ($pad) $s .= str_repeat('=', 4 - $pad);
        return base64_decode($s, true);
    }
}
