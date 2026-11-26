<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Ganti kata sandi akun sendiri dari halaman Profil.
 *
 * Backend saat ini hanya menyediakan reset kata sandi oleh Admin (PUT /api/admin/users/{id}/password),
 * jadi Admin dapat mengganti kata sandinya sendiri di sini, sedangkan karyawan meminta Admin meresetnya
 * lewat Kelola Akun. Bila Backend kelak menambah endpoint untuk semua akun, cukup ubah bisaGanti() dan endpoint-nya.
 */
class KataSandiController extends Controller
{
    /** Role yang bisa mengganti kata sandinya sendiri dari Profil. */
    public static function bisaGanti(?string $role): bool
    {
        return $role === 'admin';
    }

    public function ganti(Request $request)
    {
        $user = session('user', []);
        abort_unless(self::bisaGanti($user['role'] ?? null), 403);

        $data = $request->validateWithBag('sandi', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Isi kata sandi saat ini.',
            'password.required' => 'Isi kata sandi baru.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ]);

        // 1) Pastikan kata sandi saat ini benar (login sementara ke Backend, lalu tokennya langsung dicabut)
        try {
            $check = Http::acceptJson()->timeout(10)->post(BackendApiService::url('login'), [
                'email' => $user['email'] ?? '',
                'password' => $data['current_password'],
            ]);
        } catch (\Throwable $e) {
            return $this->gagal('Server sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.');
        }
        if ($check->status() === 429) {
            return $this->gagal('Terlalu banyak percobaan. Tunggu sekitar 1 menit lalu coba lagi.');
        }
        if (! $check->successful()) {
            return $this->gagal('Kata sandi saat ini salah.', 'current_password');
        }
        Http::withToken($check->json('token'))->acceptJson()->post(BackendApiService::url('logout'));

        // 2) Simpan kata sandi baru. Backend mengeluarkan semua sesi akun ini, jadi pengguna masuk ulang.
        $res = BackendApiService::send('PUT', "admin/users/{$user['id']}/password", ['password' => $data['password']]);
        if (! $res['ok']) {
            return $this->gagal($res['first_error'] ?? $res['message']);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Kata sandi berhasil diganti. Silakan masuk dengan kata sandi baru.');
    }

    private function gagal(string $message, string $field = 'password')
    {
        return back()->withErrors([$field => $message], 'sandi');
    }
}
