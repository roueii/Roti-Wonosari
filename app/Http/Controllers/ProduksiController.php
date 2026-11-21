<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KpiHistoryService;
use App\Services\KpiDashboardService;

class ProduksiController extends Controller
{
    /** Item ceklis Produksi (Figma) -> kode indikator Backend (posisi Produksi). */
    private const BACKEND_MAP = [
        'target_roti' => 'PRD_OUTPUT',
        'reject_maksimal' => 'PRD_REJECT',
        'waste_terkontrol' => 'PRD_RMWASTE',
        'selesai_sebelum_jadwal' => 'PRD_ONTIME',
        'tidak_terlambat_buka' => 'PRD_ONTIME',
        'perawatan_harian' => 'PRD_EQUIP',
        'bebas_kerusakan_operator' => 'PRD_EQUIP',
        'tidak_terlambat' => 'HR_ATTEND',
        'tidak_pulang_awal' => 'HR_ATTEND',
        'tidak_mangkir' => 'HR_ATTEND',
        'menimbang_resep' => 'PRD_SOP',
        'waktu_fermentasi' => 'PRD_SOP',
        'menggunakan_apd' => 'PRD_SOP',
        'mencatat_batch' => 'PRD_SOP',
        'meja_bersih' => 'CLN_PROD',
        'oven_bersih' => 'CLN_PROD',
        'mixer_bersih' => 'CLN_PROD',
        'lantai_bersih' => 'CLN_PROD',
        'rak_bersih' => 'CLN_PROD',
        'tempat_sampah' => 'CLN_PROD',
    ];

    protected array $defaultUser = ['name' => 'User', 'role' => 'produksi', 'role_label' => 'Produksi', 'email' => 'user@capaian.id'];

    /**
     * Tampilkan halaman utama Dashboard Karyawan Produksi
     */
    public function dashboard()
    {
        $user = $this->currentUser();

        $todayBadge = 'Hari ini, ' . now()->locale('id')->translatedFormat('j M');

        // Semua data dari Backend (my-kpis + riwayat); kosong bila belum ada data
        $data = KpiDashboardService::forDashboard(route('produksi.hasil_produksi'), 'produksi.riwayat.detail');
        ['metricsSummary' => $metricsSummary, 'progressRows' => $progressRows, 'dailyTask' => $dailyTask, 'recentAchievements' => $recentAchievements] = $data;

        return view('produksi.dashboard', compact('user', 'todayBadge', 'metricsSummary', 'dailyTask', 'recentAchievements'));
    }

    /**
     * Langkah 1 dari 5: Hasil Produksi
     */
    public function isiHasilProduksi()
    {
        $user = $this->currentUser();

        $stepData = session('produksi_capaian.step1', [
            'target_roti' => false,
            'reject_maksimal' => false,
            'waste_terkontrol' => false,
        ]);

        return view('produksi.hasil_produksi', compact('user', 'stepData'));
    }

    /**
     * Simpan Langkah 1
     */
    public function simpanHasilProduksi(Request $request)
    {
        $indicators = $request->input('indicators', []);
        session(['produksi_capaian.step1' => $indicators]);

        return redirect()->route('produksi.waktu_alat');
    }

    /**
     * Langkah 2 dari 5: Waktu & Alat
     */
    public function isiWaktuAlat()
    {
        $user = $this->currentUser();

        $stepData = session('produksi_capaian.step2', [
            'selesai_sebelum_jadwal' => false,
            'tidak_terlambat_buka' => false,
            'bebas_kerusakan_operator' => false,
            'perawatan_harian' => false,
            'photos' => [],
        ]);

        return view('produksi.waktu_alat', compact('user', 'stepData'));
    }

    /**
     * Simpan Langkah 2
     */
    public function simpanWaktuAlat(Request $request)
    {
        $indicators = $request->input('indicators', []);
        \App\Services\KpiBackendSubmitter::stashPhotos($request, 'PRD_EQUIP');
        session(['produksi_capaian.step2' => $indicators]);

        return redirect()->route('produksi.kehadiran_disiplin');
    }

    /**
     * Langkah 3 dari 5: Kehadiran & Disiplin
     */
    public function isiKehadiranDisiplin()
    {
        $user = $this->currentUser();

        $stepData = session('produksi_capaian.step3', [
            'tidak_terlambat' => false,
            'tidak_pulang_awal' => false,
            'tidak_mangkir' => false,
        ]);

        return view('produksi.kehadiran_disiplin', compact('user', 'stepData'));
    }

    /**
     * Simpan Langkah 3
     */
    public function simpanKehadiranDisiplin(Request $request)
    {
        $indicators = $request->input('indicators', []);
        session(['produksi_capaian.step3' => $indicators]);

        return redirect()->route('produksi.kepatuhan_sop');
    }

    /**
     * Langkah 4 dari 5: Kepatuhan SOP
     */
    public function isiKepatuhanSop()
    {
        $user = $this->currentUser();

        $stepData = session('produksi_capaian.step4', [
            'menimbang_resep' => false,
            'waktu_fermentasi' => false,
            'menggunakan_apd' => false,
            'mencatat_batch' => false,
        ]);

        return view('produksi.kepatuhan_sop', compact('user', 'stepData'));
    }

    /**
     * Simpan Langkah 4
     */
    public function simpanKepatuhanSop(Request $request)
    {
        $indicators = $request->input('indicators', []);
        session(['produksi_capaian.step4' => $indicators]);

        return redirect()->route('produksi.kebersihan_area');
    }

    /**
     * Langkah 5 dari 5: Kebersihan Area Kerja
     */
    public function isiKebersihanArea()
    {
        $user = $this->currentUser();

        $stepData = session('produksi_capaian.step5', [
            'meja_bersih' => false,
            'oven_bersih' => false,
            'mixer_bersih' => false,
            'lantai_bersih' => false,
            'rak_bersih' => false,
            'tempat_sampah' => false,
            'photos' => [],
        ]);

        return view('produksi.kebersihan_area', compact('user', 'stepData'));
    }

    /**
     * Simpan Langkah 5 & Kirim Semua
     */
    public function simpanKebersihanArea(Request $request)
    {
        $indicators = $request->input('indicators', []);
        \App\Services\KpiBackendSubmitter::stashPhotos($request, 'CLN_PROD');
        session(['produksi_capaian.step5' => $indicators]);

        // Kirim ke Backend: gabungan item yang dicentang di kelima langkah
        $checked = [];
        foreach (['step1', 'step2', 'step3', 'step4', 'step5'] as $step) {
            $checked = array_merge($checked, $this->checkedKeys(session("produksi_capaian.{$step}", [])));
        }
        if ($error = $this->kirimCapaianKeBackend($request, self::BACKEND_MAP, $checked, 'produksi.kebersihan_area')) {
            return $error;
        }

        session([
            'produksi_capaian_completed' => true,
            'produksi_capaian_completed_at' => now()->toDateTimeString(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Seluruh capaian produksi harian berhasil dikirim!',
                'redirect' => route('produksi.dashboard'),
            ]);
        }

        return redirect()->route('produksi.dashboard')->with('status', 'Seluruh capaian produksi harian berhasil dikirim!');
    }

    /**
     * Simpan Draf Sementara
     */
    public function simpanDraft(Request $request)
    {
        // Draf berasal dari langkah terakhir; simpan ke session langkah 5 agar
        // centang muncul kembali saat halaman dibuka lagi.
        session(['produksi_capaian.step5' => $request->input('indicators', [])]);

        session([
            'produksi_capaian_draft' => [
                'indicators' => $request->input('indicators', []),
                'drafted_at' => now()->toDateTimeString(),
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Capaian produksi berhasil disimpan sebagai draf sementara!',
            'redirect' => route('produksi.dashboard'),
        ]);
    }

    /**
     * Halaman Riwayat Capaian Produksi Sesuai Figma
     */
    public function riwayatCapaian(Request $request)
    {
        $user = $this->currentUser();

        // Riwayat dari Backend (GET /api/my-kpis/history); kosong bila belum ada / gagal dimuat
        $history = KpiHistoryService::list();
        $summaryCounts = $history['summary'] ?? ['disetujui' => 0, 'menunggu' => 0, 'revisi' => 0, 'semua' => 0];
        $riwayatList = $history['items'] ?? [];

        $filter = $request->query('status', 'disetujui');

        return view('produksi.riwayat', compact('user', 'summaryCounts', 'riwayatList', 'filter'));
    }

    /**
     * Halaman Detail Riwayat Capaian Produksi Sesuai Figma
     * (Mendukung Tampilan Disetujui & Perlu Revisi)
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
        $isRevision = $live['is_revision'];
        $detailRiwayat = $this->liveDetailRiwayat($live, route('produksi.hasil_produksi'));

        return view('produksi.detail_riwayat', compact('user', 'detailRiwayat', 'isRevision'));
    }

    /**
     * Halaman Profil Produksi Sesuai Figma
     */
    public function profil()
    {
        $user = $this->profileUser([
            'name' => 'User',
            'role' => 'produksi',
            'role_label' => 'PRODUKSI',
            'status_label' => 'Akun Aktif',
            'status' => 'active',
        ]);

        if (!empty($user['role_label'])) {
            $user['role_label'] = strtoupper($user['role_label']);
        }

        return view('produksi.profil', compact('user'));
    }
}

