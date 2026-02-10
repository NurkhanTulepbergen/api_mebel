<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Http\Traits\TwoFactorTrait;
use App\Models\{
    User,
    RefreshToken
};
/**
 * @group Login
 */
class UserController extends Controller
{
    use TwoFactorTrait;

    public function login(Request $request) {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Wrong login or password'], 401);
        }
        $user->tokens()->delete();
        $token = $user->createToken($user->name.' login');
        $token->accessToken->expires_at = now()->addMinutes(60);
        $token->accessToken->save();

        RefreshToken::where('user_id', $user->id)->delete();

        $refreshToken = Str::random(64);
        $hashedRefreshToken = hash('sha256', $refreshToken);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => $hashedRefreshToken,
            'expires_at' => now()->addDays(1),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'access_token' => $token->plainTextToken,
            'refresh_token' => $refreshToken,
        ], 200);
    }

    // public function attachTwoFactor(Request $request) {
    //     $validated = $request->validate([
    //         'email' => ['required', 'string'],
    //         'password' => ['required', 'string'],
    //     ]);
    //     $user = User::where('email', $validated['email'])->first();

    //     if (!$user || !Hash::check($validated['password'], $user->password)) {
    //         return response()->json(['message' => 'Wrong login or password'], 401);
    //     }
    //     if($user->google2fa_secret) {
    //         return response()->json(['message' => 'Google Authentificator was already got'], 401);
    //     }

    //     $secret = $this->generateSecret();
    //     $user->update([
    //         'google2fa_secret' => encrypt($secret)
    //     ]);

    //     return response()->json([
    //         'message' => 'secret has been obtained',
    //         'secret' => $secret
    //     ], 200);
    // }

    public function refreshToken(Request $request) {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);
        $refresh = $validated['refresh_token'];
        $hashed = hash('sha256', $refresh);

        $tokenRecord = RefreshToken::where('token', $hashed)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        // Удалим старый refresh token
        $tokenRecord->delete();

        $user = $tokenRecord->user;
        $accessToken = $user->createToken($user->name.' refreshed');
        $accessToken->accessToken->expires_at = now()->addMinutes(59);
        $accessToken->accessToken->save();

        // Создаем новый refresh token
        $newRaw = Str::random(64);
        $newHashed = hash('sha256', $newRaw);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => $newHashed,
            'expires_at' => now()->addDays(1),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $newRaw,
        ]);
    }

    public function showUser(Request $request) {
        $user = $request->user();
        $refreshToken = $user->latestRefreshToken->expires_at;
        $accessToken = $request->user()->currentAccessToken()->expires_at;
        $refreshTokenExpiration = intval(Carbon::parse($refreshToken)->diffInMinutes(Carbon::now())) * -1;
        $accessTokenExpiration = intval(Carbon::parse($accessToken)->diffInMinutes(Carbon::now())) * -1;
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'access_token_expired_in' => $accessTokenExpiration,
            'refresh_token_expired_in' => $refreshTokenExpiration,
        ];

        return response()->json([
            'data' => $data
        ], 200);
    }
    public function register(Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }
}
