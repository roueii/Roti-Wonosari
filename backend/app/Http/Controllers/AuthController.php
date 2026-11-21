<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with(['role', 'position'])
            ->where('email', $credentials['email'])
            ->first();

        // Pesan dibedakan sesuai desain FE (Figma halaman Login): email tidak terdaftar vs kata sandi salah
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak sesuai. Periksa kembali email yang Anda masukkan.',
            ]);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Kata sandi salah. Periksa kembali kata sandi yang Anda masukkan.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Akun tidak aktif.']);
        }

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user'  => $this->formatUser($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->formatUser($request->user()->load(['role', 'position'])));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'role'     => $user->role?->only(['id', 'name']),
            'position'   => $user->position?->only(['id', 'name']),
            'is_active'  => (bool) $user->is_active,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}