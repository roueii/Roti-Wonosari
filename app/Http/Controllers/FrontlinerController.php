<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KpiHistoryService;
use App\Services\KpiDashboardService;

class FrontlinerController extends Controller
{
    /**
     * Item ceklis Frontliner (Figma) -> kode indikator Backend (posisi Frontliner).
     * Beberapa item ke satu kode: indikator tercapai bila semua item terkait dicentang.
     */
    private const BACKEND_MAP = [
        'target_omzet' => 'SAL_TARGET',
        'rata_rata_belanja' => 'SAL_BASKET',
        'tawarkan_promo' => 'SAL_UPSELL',
        'menyapa_pelanggan' => 'SVC_QUALITY',
        'ramah_komunikatif' => 'SVC_QUALITY',
        'cepat_melayani' => 'SVC_QUALITY',
        'menangani_komplain' => 'SVC_QUALITY',
        'fungsi_bahan_kue' => 'SVC_PRODKNOW',
        'stok_lokasi_produk' => 'SVC_PRODKNOW',
        'rekomendasi_pelanggan' => 'SVC_PRODKNOW',
        'tidak_ada_selisih' => 'FIN_CASHACC',
        'tidak_salah_harga' => 'FIN_CASHACC',
        'nota_sesuai' => 'FIN_CASHACC',
        'rak_selalu_rapi' => 'CLN_DISPLAY',
        'produk_fifo' => 'CLN_DISPLAY',
        'label_harga_lengkap' => 'CLN_DISPLAY',
        'area_kerja_bersih' => 'CLN_DISPLAY',
        'seragam_lengkap' => 'OPS_SOP',
        'sop_buka_tutup' => 'OPS_SOP',
        'prosedur_keamanan' => 'OPS_SOP',
        'tidak_terlambat' => 'HR_ATTEND',
        'jadwal_kerja' => 'HR_ATTEND',
    ];

    protected array $defaultUser = ['name' => 'User', 'role' => 'frontliner', 'role_label' => 'Frontliner', 'email' => 'User@capaian.id'];

    /**
     * Tampilkan halaman utama Dashboard Frontliner (Karyawan) Sesuai Figma Revisi
     */
    public function dashboard()
    {
        $user = $this->currentUser();

        // Badge tanggal hari ini sesuai Figma Revisi
        $todayBadge = 'Hari ini, ' . now()->locale('id')->translatedFormat('j M');

        // Semua data dari Backend (my-kpis + riwayat); kosong bila belum ada data
        $data = KpiDashboardService::forDashboard(route('frontliner.penjualan'), 'frontliner.riwayat.detail');
        ['metricsSummary' => $metricsSummary, 'progressRows' => $progressRows, 'dailyTask' => $dailyTask, 'recentAchievements' => $recentAchievements] = $data;

        return view('staff.dashboard', compact(
            'user',
            'todayBadge',
            'metricsSummary',
            'progressRows',
            'dailyTask',
            'recentAchievements'
        ));
    }

    // =========================================================================
    // MULTI-STEP FORM PENGISIAN CAPAIAN FRONTLINER (6 LANGKAH REVISI)
    // =========================================================================

    /**
     * Langkah 1 dari 6: Penjualan
     */
    public function isiPenjualan()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 1,
            'total' => 6,
            'title' => 'Penjualan',
            'subtitle' => 'Input total perolehan transaksi kasir shift hari ini.',
            'category_name' => 'Penjualan',
            'next_route' => route('frontliner.pelayanan'),
            'back_route' => route('frontliner.dashboard'),
        ];

        // Definisi item: config/kpi_form.php (semua ceklis mulai kosong)
        $indicators = config('kpi_form.frontliner.penjualan');

        $existingPhotos = [];

        return view('staff.penjualan', compact('user', 'stepInfo', 'indicators', 'existingPhotos'));
    }

    /**
     * Simpan Langkah 1: Penjualan
     */
    public function simpanPenjualan(Request $request)
    {
        \App\Services\KpiBackendSubmitter::stashPhotos($request, 'SAL_TARGET');

        session([
            'capaian_penjualan' => [
                'indicators' => $request->input('indicators', []),
                'photos' => $request->input('photos', []),
                'saved_at' => now()->toDateTimeString(),
            ]
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Capaian Penjualan berhasil disimpan! Lanjut ke Pelayanan...',
                'redirect' => route('frontliner.pelayanan'),
            ]);
        }

        return redirect()->route('frontliner.pelayanan')->with('status', 'Penjualan berhasil disimpan!');
    }

    /**
     * Langkah 2 dari 6: Pelayanan
     */
    public function isiPelayanan()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 2,
            'total' => 6,
            'title' => 'Pelayanan',
            'subtitle' => 'Input total perolehan transaksi kasir shift hari ini.',
            'category_name' => 'Pelayanan',
            'next_route' => route('frontliner.knowledge'),
            'back_route' => route('frontliner.penjualan'),
        ];

        // Definisi item: config/kpi_form.php (semua ceklis mulai kosong)
        $indicators = config('kpi_form.frontliner.pelayanan');

        return view('staff.pelayanan', compact('user', 'stepInfo', 'indicators'));
    }

    /**
     * Simpan Langkah 2: Pelayanan
     */
    public function simpanPelayanan(Request $request)
    {
        session([
            'capaian_pelayanan' => [
                'indicators' => $request->input('indicators', []),
                'saved_at' => now()->toDateTimeString(),
            ]
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Capaian Pelayanan berhasil disimpan! Lanjut ke Product Knowledge...',
                'redirect' => route('frontliner.knowledge'),
            ]);
        }

        return redirect()->route('frontliner.knowledge')->with('status', 'Pelayanan berhasil disimpan!');
    }

    /**
     * Langkah 3 dari 6: Product Knowledge
     */
    public function isiProductKnowledge()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 3,
            'total' => 6,
            'title' => 'Product Knowledge',
            'subtitle' => 'Input total perolehan transaksi kasir shift hari ini.',
            'category_name' => 'Product Knowledge',
            'next_route' => route('frontliner.kasir'),
            'back_route' => route('frontliner.pelayanan'),
        ];

        // Definisi item: config/kpi_form.php (semua ceklis mulai kosong)
        $indicators = config('kpi_form.frontliner.product_knowledge');

        return view('staff.knowledge', compact('user', 'stepInfo', 'indicators'));
    }

    /**
     * Simpan Langkah 3: Product Knowledge
     */
    public function simpanProductKnowledge(Request $request)
    {
        session([
            'capaian_knowledge' => [
                'indicators' => $request->input('indicators', []),
                'saved_at' => now()->toDateTimeString(),
            ]
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Capaian Product Knowledge berhasil disimpan! Lanjut ke Akurasi Kasir...',
                'redirect' => route('frontliner.kasir'),
            ]);
        }

        return redirect()->route('frontliner.kasir')->with('status', 'Product Knowledge berhasil disimpan!');
    }

    /**
     * Langkah 4 dari 6: Akurasi Kasir
     */
    public function isiAkurasiKasir()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 4,
            'total' => 6,
            'title' => 'Akurasi Kasir',
            'subtitle' => 'Input total perolehan transaksi kasir shift hari ini.',
            'category_name' => 'Akurasi Kasir',
            'next_route' => route('frontliner.display'),
            'back_route' => route('frontliner.knowledge'),
        ];

        // Definisi item: config/kpi_form.php (semua ceklis mulai kosong)
        $indicators = config('kpi_form.frontliner.akurasi_kasir');

        return view('staff.kasir', compact('user', 'stepInfo', 'indicators'));
    }

    /**
     * Simpan Langkah 4: Akurasi Kasir
     */
    public function simpanAkurasiKasir(Request $request)
    {
        session([
            'capaian_kasir' => [
                'indicators' => $request->input('indicators', []),
                'saved_at' => now()->toDateTimeString(),
            ]
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Capaian Akurasi Kasir berhasil disimpan! Lanjut ke Display & Kebersihan...',
                'redirect' => route('frontliner.display'),
            ]);
        }

        return redirect()->route('frontliner.display')->with('status', 'Akurasi Kasir berhasil disimpan!');
    }

    /**
     * Langkah 5 dari 6: Display & Kebersihan
     */
    public function isiDisplayKebersihan()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 5,
            'total' => 6,
            'title' => 'Display & Kebersihan',
            'subtitle' => 'Input total perolehan transaksi kasir shift hari ini.',
            'category_name' => 'Display & Kebersihan',
            'next_route' => route('frontliner.kepatuhan'),
            'back_route' => route('frontliner.kasir'),
        ];

        // Definisi item: config/kpi_form.php (semua ceklis mulai kosong)
        $indicators = config('kpi_form.frontliner.display_kebersihan');

        $existingPhotos = [];

        return view('staff.display', compact('user', 'stepInfo', 'indicators', 'existingPhotos'));
    }

    /**
     * Simpan Langkah 5: Display & Kebersihan
     */
    public function simpanDisplayKebersihan(Request $request)
    {
        \App\Services\KpiBackendSubmitter::stashPhotos($request, 'CLN_DISPLAY');

        session([
            'capaian_display' => [
                'indicators' => $request->input('indicators', []),
                'photos' => $request->input('photos', []),
                'saved_at' => now()->toDateTimeString(),
            ]
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Capaian Display & Kebersihan berhasil disimpan! Lanjut ke Kepatuhan SOP & Disiplin...',
                'redirect' => route('frontliner.kepatuhan'),
            ]);
        }

        return redirect()->route('frontliner.kepatuhan')->with('status', 'Display & Kebersihan berhasil disimpan!');
    }

    /**
     * Langkah 6 dari 6: Kepatuhan SOP & Disiplin
     */
    public function isiKepatuhanSop()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 6,
            'total' => 6,
            'title' => 'Kepatuhan SOP & Disiplin',
            'subtitle' => 'Input total perolehan transaksi kasir shift hari ini.',
            'category_name' => 'Kepatuhan SOP',
            'back_route' => route('frontliner.display'),
        ];

        $sopIndicators = config('kpi_form.frontliner.kepatuhan_sop.sop');

        $disiplinIndicators = config('kpi_form.frontliner.kepatuhan_sop.disiplin');

        // Pulihkan centang dari draf / data tersimpan sebelumnya
        $saved = session('capaian_kepatuhan') ?? session('capaian_frontliner_draft');
        if (is_array($saved)) {
            foreach ($sopIndicators as &$item) {
                $item['checked'] = in_array($item['id'], $saved['sop'] ?? [], true);
            }
            foreach ($disiplinIndicators as &$item) {
                $item['checked'] = in_array($item['id'], $saved['disiplin'] ?? [], true);
            }
            unset($item);
        }

        return view('staff.kepatuhan', compact('user', 'stepInfo', 'sopIndicators', 'disiplinIndicators'));
    }

    /**
     * Simpan Langkah 6: Kepatuhan SOP & Disiplin (Final Submit)
     */
    public function simpanKepatuhanSop(Request $request)
    {
        session([
            'capaian_kepatuhan' => [
                'sop' => $request->input('sop', []),
                'disiplin' => $request->input('disiplin', []),
                'saved_at' => now()->toDateTimeString(),
                'status' => 'submitted',
            ]
        ]);

        // Kirim ke Backend: gabungan item yang dicentang di keenam langkah
        $checked = [];
        foreach (['capaian_penjualan', 'capaian_pelayanan', 'capaian_knowledge', 'capaian_kasir', 'capaian_display'] as $key) {
            $checked = array_merge($checked, $this->checkedKeys(session("{$key}.indicators", [])));
        }
        $checked = array_merge($checked, $this->checkedKeys($request->input('sop', [])), $this->checkedKeys($request->input('disiplin', [])));
        if ($error = $this->kirimCapaianKeBackend($request, self::BACKEND_MAP, $checked, 'frontliner.kepatuhan')) {
            session(['capaian_kepatuhan.status' => 'draft']);

            return $error;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Seluruh capaian frontliner (6/6 kategori) berhasil dikirim ke Store Manager!',
                'redirect' => route('frontliner.dashboard'),
            ]);
        }

        return redirect()->route('frontliner.dashboard')->with('status', 'Seluruh capaian frontliner berhasil dikirim ke Store Manager!');
    }

    /**
     * Simpan sebagai Draf Sementara
     */
    public function simpanDraft(Request $request)
    {
        session(['capaian_frontliner_draft' => [
            'sop' => $request->input('sop', []),
            'disiplin' => $request->input('disiplin', []),
            'drafted_at' => now()->toDateTimeString(),
        ]]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Draf pengisian capaian berhasil disimpan sementara.',
                'redirect' => route('frontliner.dashboard'),
            ]);
        }

        return redirect()->route('frontliner.dashboard')->with('status', 'Draf capaian berhasil disimpan sementara.');
    }

    // =========================================================================
    // HALAMAN RIWAYAT & DETAIL RIWAYAT FRONTLINER (SESUAI FIGMA REVISI)
    // =========================================================================

    /**
     * Halaman Riwayat Capaian
     */
    public function riwayatCapaian()
    {
        $user = $this->currentUser();

        // Riwayat dari Backend (GET /api/my-kpis/history); kosong bila belum ada / gagal dimuat
        $history = KpiHistoryService::list();
        $summary = $history['summary'] ?? ['disetujui' => 0, 'menunggu' => 0, 'revisi' => 0, 'semua' => 0];
        $historyList = $history['items'] ?? [];

        return view('staff.riwayat', compact('user', 'summary', 'historyList'));
    }

    /**
     * Halaman Detail Riwayat (Mendukung Disetujui & Perlu Revisi)
     */
    public function detailRiwayat(Request $request, $id = null)
    {
        // Tautan dari daftar Riwayat memakai /riwayat/detail?id=... (route tanpa {id})
        $id ??= $request->query('id');

        $user = $this->currentUser();

        // Detail dari Backend (GET /api/my-kpis/history/{date}); id = tanggal laporan (Ymd)
        $date = KpiHistoryService::dateFromId($id);
        $live = $date ? KpiHistoryService::detail($date) : null;
        abort_unless($live, 404, 'Laporan tidak ditemukan.');
        [$reportMeta, $revisionNote, $categories] = $this->liveReportDetail($live, route('frontliner.penjualan'));

        return view('staff.detail_riwayat', compact('user', 'reportMeta', 'revisionNote', 'categories'));
    }

    /**
     * Halaman Profil Frontliner
     */
    public function profil()
    {
        $user = $this->profileUser([
            'name' => 'User',
            'status_label' => 'Akun Aktif',
        ]);
        $user['role_label'] = 'FRONTLINER';

        return view('staff.profil', compact('user'));
    }
}
