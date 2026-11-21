<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use Closure;
use Illuminate\Http\Request;

/**
 * Halaman hanya untuk pengguna yang sudah login (token Backend ada di sesi) dengan role yang sesuai.
 * Pemakaian: ->middleware('masuk:admin') atau ->middleware('masuk:sales_marketing,supervisor')
 */
class WajibMasuk
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! session('api_token') || ! session('user')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi berakhir. Silakan masuk kembali.'], 401);
            }

            return redirect()->route('login')->with('status', 'Silakan masuk terlebih dahulu.');
        }

        $role = session('user.role');
        if ($roles && ! in_array($role, $roles, true)) {
            // Role lain: arahkan ke dashboard miliknya sendiri
            return redirect()->route(AuthController::dashboardRoute($role));
        }

        return $next($request);
    }
}
