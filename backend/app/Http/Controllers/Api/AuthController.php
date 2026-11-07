<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Email dan password TIDAK dimasukkan ke log. Untuk akun yang ada, yang
        // dicatat hanya user_id; untuk akun yang tidak dikenal, tidak ada pengenal.
        if (! $user) {
            Log::warning('Login gagal: akun tidak dikenal', [
                'event' => 'auth.login.failed',
                'user_id' => null,
                'reason' => 'unknown_account',
            ]);

            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            Log::warning('Login gagal: kata sandi salah', [
                'event' => 'auth.login.failed',
                'user_id' => $user->id,
                'reason' => 'wrong_password',
            ]);

            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $token = $user->createToken('vue-frontend')->plainTextToken;

        Log::info('Login berhasil', [
            'event' => 'auth.login.succeeded',
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        Log::info('Logout berhasil', [
            'event' => 'auth.logout',
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }
}
