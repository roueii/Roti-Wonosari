<?php

namespace App\Http\Middleware;

use App\Services\AksesAkunService;
use Closure;
use Illuminate\Http\Request;

/**
 * Keluarkan karyawan yang "Status Akun Aktif"-nya dimatikan admin saat masih login,
 * supaya tidak bisa lanjut mengisi KPI harian.
 */
class CekAksesAkun
{
    public function handle(Request $request, Closure $next)
    {
        $user = session('user');

        if ($user && ($user['role'] ?? '') !== 'admin' && ! AksesAkunService::bisaAkses($user['email'] ?? null)) {
            session()->forget(['user', 'api_token']);

            return redirect()->route('login')->withErrors(['email' => AksesAkunService::PESAN_DIBATASI]);
        }

        return $next($request);
    }
}
