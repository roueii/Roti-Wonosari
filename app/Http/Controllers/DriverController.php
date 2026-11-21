<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KpiHistoryService;
use App\Services\KpiDashboardService;

class DriverController extends Controller
{
    /** Item ceklis Driver (Figma) -> kode indikator Backend (posisi Driver). */
    private const BACKEND_MAP = [
        'datang_tepat' => 'DLV_ONTIME',
        'tidak_terlambat' => 'DLV_ONTIME',
        'prioritas_pengiriman' => 'DLV_ONTIME',
        'kondisi_baik' => 'DLV_SAFETY',
        'penataan_aman' => 'DLV_SAFETY',
        'tidak_komplain' => 'DLV_SAFETY',
        'surat_jalan' => 'DLV_ADMIN',
        'tanda_tangan' => 'DLV_ADMIN',
        'kembalikan_dokumen' => 'DLV_ADMIN',
        'kebersihan' => 'DLV_VEHICLE',
        'cek_rem' => 'DLV_VEHICLE',
        'cek_oli' => 'DLV_VEHICLE',
        'cek_ban' => 'DLV_VEHICLE',
        'cek_lampu' => 'DLV_VEHICLE',
        'lapor_kerusakan' => 'DLV_VEHICLE',
        'tidak_boros_bbm' => 'DLV_FUEL',
        'tidak_pribadi' => 'DLV_FUEL',
        'rute_efektif' => 'DLV_FUEL',
        'kerapian' => 'HR_ATTEND',
        'briefing' => 'HR_ATTEND',
        'komunikasi' => 'SVC_QUALITY',
        'tidak_konflik' => 'SVC_QUALITY',
    ];

    protected array $defaultUser = ['name' => 'User', 'role' => 'driver', 'role_label' => 'Driver', 'email' => 'user@capaian.id'];

    /**
     * Tampilkan halaman utama Dashboard Driver (Karyawan Driver)
     */
    public function dashboard()
    {
        $user = $this->currentUser();

        $todayBadge = 'Hari ini, ' . now()->locale('id')->translatedFormat('j M');

        // Semua data dari Backend (my-kpis + riwayat); kosong bila belum ada data
        $data = KpiDashboardService::forDashboard(route('driver.pengiriman'), 'driver.riwayat.detail');
        ['metricsSummary' => $metricsSummary, 'progressRows' => $progressRows, 'dailyTask' => $dailyTask, 'recentAchievements' => $recentAchievements] = $data;

        return view('driver.dashboard', compact('user', 'todayBadge', 'metricsSummary', 'dailyTask', 'recentAchievements'));
    }

    /**
     * Langkah 1: Ketepatan Pengiriman & Keamanan Barang
     */
    public function isiPengiriman()
    {
        $user = $this->currentUser();

        $stepData = session('driver_capaian.step1', [
            'datang_tepat' => false,
            'tidak_terlambat' => false,
            'prioritas_pengiriman' => false,
            'kondisi_baik' => false,
            'penataan_aman' => false,
            'tidak_komplain' => false,
        ]);

        return view('driver.pengiriman', compact('user', 'stepData'));
    }

    /**
     * Simpan Langkah 1
     */
    public function simpanPengiriman(Request $request)
    {
        $indicators = $request->input('indicators', []);
        session(['driver_capaian.step1' => $indicators]);

        return redirect()->route('driver.administrasi');
    }

    /**
     * Langkah 2: SOP Administrasi
     */
    public function isiAdministrasi()
    {
        $user = $this->currentUser();

        $stepData = session('driver_capaian.step2', [
            'surat_jalan' => false,
            'tanda_tangan' => false,
            'kembalikan_dokumen' => false,
            'photos' => [],
        ]);

        return view('driver.administrasi', compact('user', 'stepData'));
    }

    /**
     * Simpan Langkah 2
     */
    public function simpanAdministrasi(Request $request)
    {
        $indicators = $request->input('indicators', []);
        \App\Services\KpiBackendSubmitter::stashPhotos($request, 'DLV_ADMIN');
        session(['driver_capaian.step2' => $indicators]);

        return redirect()->route('driver.kendaraan');
    }

    /**
     * Langkah 3: Kendaraan & BBM
     */
    public function isiKendaraan()
    {
        $user = $this->currentUser();

        $stepData = session('driver_capaian.step3', [
            'kebersihan' => false,
            'cek_rem' => false,
            'cek_oli' => false,
            'cek_ban' => false,
            'cek_lampu' => false,
            'lapor_kerusakan' => false,
            'tidak_boros_bbm' => false,
            'tidak_pribadi' => false,
            'rute_efektif' => false,
        ]);

        return view('driver.kendaraan', compact('user', 'stepData'));
    }

    /**
     * Simpan Langkah 3
     */
    public function simpanKendaraan(Request $request)
    {
        $indicators = $request->input('indicators', []);
        session(['driver_capaian.step3' => $indicators]);

        return redirect()->route('driver.pelayanan');
    }

    /**
     * Langkah 4: Disiplin & Pelayanan
     */
    public function isiPelayanan()
    {
        $user = $this->currentUser();

        $stepData = session('driver_capaian.step4', [
            'kerapian' => false,
            'briefing' => false,
            'komunikasi' => false,
            'tidak_konflik' => false,
            'photos' => [],
        ]);

        return view('driver.pelayanan', compact('user', 'stepData'));
    }

    /**
     * Simpan Langkah 4 & Kirim Semua
     */
    public function simpanPelayanan(Request $request)
    {
        $indicators = $request->input('indicators', []);
        \App\Services\KpiBackendSubmitter::stashPhotos($request, 'HR_ATTEND');
        session(['driver_capaian.step4' => $indicators]);

        // Kirim ke Backend: gabungan item yang dicentang di keempat langkah
        $checked = [];
        foreach (['step1', 'step2', 'step3', 'step4'] as $step) {
            $checked = array_merge($checked, $this->checkedKeys(session("driver_capaian.{$step}", [])));
        }
        if ($error = $this->kirimCapaianKeBackend($request, self::BACKEND_MAP, $checked, 'driver.pelayanan')) {
            return $error;
        }

        // Tandai capaian hari ini selesai
        session([
            'driver_capaian_completed' => true,
            'driver_capaian_completed_at' => now()->toDateTimeString(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Seluruh capaian driver harian berhasil dikirim!',
                'redirect' => route('driver.dashboard'),
            ]);
        }

        return redirect()->route('driver.dashboard')->with('status', 'Seluruh capaian driver harian berhasil dikirim!');
    }

    /**
     * Simpan Draf Sementara
     */
    public function simpanDraft(Request $request)
    {
        // Draf berasal dari langkah terakhir; simpan ke session langkah 4 agar
        // centang muncul kembali saat halaman dibuka lagi.
        session(['driver_capaian.step4' => $request->input('indicators', [])]);

        session([
            'driver_capaian_draft' => [
                'indicators' => $request->input('indicators', []),
                'drafted_at' => now()->toDateTimeString(),
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Capaian berhasil disimpan sebagai draf sementara!',
            'redirect' => route('driver.dashboard'),
        ]);
    }

    /**
     * Halaman Riwayat Capaian Driver Sesuai Figma
     */
    public function riwayatCapaian(Request $request)
    {
        $user = $this->currentUser();

        // Riwayat dari Backend (GET /api/my-kpis/history); kosong bila belum ada / gagal dimuat
        $history = KpiHistoryService::list();
        $summaryCounts = $history['summary'] ?? ['disetujui' => 0, 'menunggu' => 0, 'revisi' => 0, 'semua' => 0];
        $riwayatList = $history['items'] ?? [];

        $filter = $request->query('status', 'disetujui');

        return view('driver.riwayat', compact('user', 'summaryCounts', 'riwayatList', 'filter'));
    }

    /**
     * Halaman Detail Riwayat Capaian Driver Sesuai Figma
     * (Mendukung Tampilan Disetujui & Perlu Perbaikan/Revisi)
     */
    public function detailRiwayat(Request $request, $id = null)
    {
        // Tautan dari daftar Riwayat memakai /riwayat/detail?id=... (route tanpa {id})
        $id ??= $request->query('id');

        $user = $this->currentUser();

        // Cek jika status revisi dari parameter atau id
        // Detail dari Backend (GET /api/my-kpis/history/{date}); id = tanggal laporan (Ymd)
        $date = KpiHistoryService::dateFromId($id);
        $live = $date ? KpiHistoryService::detail($date) : null;
        abort_unless($live, 404, 'Laporan tidak ditemukan.');
        $isRevision = $live['is_revision'];
        $detailRiwayat = $this->liveDetailRiwayat($live, route('driver.pengiriman'));

        return view('driver.detail_riwayat', compact('user', 'detailRiwayat', 'isRevision'));
    }

    /**
     * Halaman Profil Driver Sesuai Figma
     */
    public function profil()
    {
        $user = $this->profileUser([
            'name' => 'User',
            'role' => 'driver',
            'role_label' => 'DRIVER',
            'phone' => '081234567890',
            'status_label' => 'Akun Aktif',
            'status' => 'active',
        ]);

        if (!empty($user['role_label'])) {
            $user['role_label'] = strtoupper($user['role_label']);
        }

        return view('driver.profil', compact('user'));
    }
}

