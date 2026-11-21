<?php

namespace App\Http\Controllers;

use App\Services\AksesAkunService;
use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Login & logout. Semua akun berasal dari Backend KPI (POST /api/login); tidak ada akun lokal/demo.
 */
class AuthController extends Controller
{
    /** Role Frontend => nama route dashboard */
    public const DASHBOARD_ROUTES = [
        'admin' => 'admin.dashboard',
        'store_manager' => 'storemanager.dashboard',
        'supervisor' => 'supervisor.dashboard',
        'sales_marketing' => 'sales.dashboard',
        'driver' => 'driver.dashboard',
        'produksi' => 'produksi.dashboard',
        'frontliner' => 'staff.dashboard',
    ];

    public function showLogin()
    {
        if (session()->has('api_token') && session()->has('user')) {
            return redirect()->route(self::dashboardRoute(session('user.role')));
        }

        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email akun wajib diisi.',
            'email.email' => 'Email tidak sesuai. Periksa kembali email yang Anda masukkan.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $email = strtolower(trim($request->input('email')));

        try {
            $response = BackendApiService::post('login', [
                'email' => $email,
                'password' => $request->input('password'),
            ]);
        } catch (\Throwable $e) {
            return $this->gagal($request, 'email', 'Server sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.');
        }

        if ($response->status() === 429) {
            return $this->gagal($request, 'password', 'Terlalu banyak percobaan masuk. Tunggu 1 menit lalu coba lagi.');
        }

        if ($response->status() === 422) {
            // Backend membedakan email tidak terdaftar / akun nonaktif (errors.email) dan kata sandi salah (errors.password)
            $errors = $response->json('errors') ?? [];
            if (! empty($errors['password'][0])) {
                return $this->gagal($request, 'password', $errors['password'][0]);
            }

            return $this->gagal($request, 'email', $errors['email'][0] ?? 'Email tidak sesuai. Periksa kembali email yang Anda masukkan.');
        }

        if (! $response->successful() || ! $response->json('token')) {
            return $this->gagal($request, 'email', 'Login gagal diproses server. Coba lagi beberapa saat lagi.');
        }

        $token = $response->json('token');
        $apiUser = $response->json('user') ?? [];
        $uiRole = self::uiRole($apiUser);

        // Status Akun Aktif dimatikan admin -> tolak login dan cabut token Backend
        if ($uiRole !== 'admin' && ! AksesAkunService::bisaAkses($apiUser['email'] ?? $email)) {
            try {
                Http::withToken($token)->acceptJson()->post(BackendApiService::url('logout'));
            } catch (\Throwable $e) {
                // token tidak disimpan di sesi, jadi tetap tidak terpakai
            }

            return $this->gagal($request, 'email', AksesAkunService::PESAN_DIBATASI, 403);
        }

        $request->session()->regenerate();
        session([
            'api_token' => $token,
            'user' => [
                'id' => $apiUser['id'] ?? null,
                'name' => $apiUser['name'] ?? 'User',
                'email' => $apiUser['email'] ?? $email,
                'created_at' => $apiUser['created_at'] ?? null,
                'role' => $uiRole,
                'role_obj' => $apiUser['role'] ?? null,
                'position' => $apiUser['position'] ?? null,
            ],
        ]);

        $redirectUrl = route(self::dashboardRoute($uiRole));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil! Mengalihkan...',
                'redirect' => $redirectUrl,
                'role' => $uiRole,
            ]);
        }

        return redirect()->to($redirectUrl);
    }

    public function logout(Request $request)
    {
        if (session()->has('api_token')) {
            try {
                BackendApiService::post('logout');
            } catch (\Throwable $e) {
                // abaikan: sesi tetap dihapus
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar.');
    }

    /** Role Frontend dari role & jabatan Backend. */
    public static function uiRole(array $apiUser): string
    {
        $role = strtolower($apiUser['role']['name'] ?? '');
        $position = strtolower($apiUser['position']['name'] ?? '');

        return match (true) {
            $role === 'admin' => 'admin',
            str_contains($position, 'supervisor') || str_contains($position, 'spv') => 'supervisor',
            str_contains($position, 'sales') || str_contains($position, 'marketing') => 'sales_marketing',
            $role === 'manager' || str_contains($position, 'manager') => 'store_manager',
            str_contains($position, 'driver') => 'driver',
            str_contains($position, 'produksi') => 'produksi',
            default => 'frontliner',
        };
    }

    public static function dashboardRoute(?string $role): string
    {
        return self::DASHBOARD_ROUTES[$role] ?? 'staff.dashboard';
    }

    private function gagal(Request $request, string $field, string $message, int $status = 422)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'field' => $field, 'message' => $message], $status);
        }

        return back()->withInput($request->only('email'))->withErrors([$field => $message]);
    }
}
