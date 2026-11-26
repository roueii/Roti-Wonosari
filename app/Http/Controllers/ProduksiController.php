<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KpiHistoryService;
use App\Services\KpiDashboardService;

class ProduksiController extends Controller
{
    protected array $defaultUser = ['name' => 'User', 'role' => 'produksi', 'role_label' => 'Produksi', 'email' => 'user@capaian.id'];

    /**
     * Tampilkan halaman utama Dashboard Karyawan Produksi
     */
    public function dashboard()
    {
        $user = $this->currentUser();

        $todayBadge = 'Hari ini, ' . now()->locale('id')->translatedFormat('j M');

        // Semua data dari Backend (my-kpis + riwayat); kosong bila belum ada data
        $data = KpiDashboardService::forDashboard(\App\Http\Controllers\KpiFormController::startUrl('produksi'), 'produksi.riwayat.detail');
        ['metricsSummary' => $metricsSummary, 'progressRows' => $progressRows, 'dailyTask' => $dailyTask, 'recentAchievements' => $recentAchievements] = $data;

        return view('produksi.dashboard', compact('user', 'todayBadge', 'metricsSummary', 'dailyTask', 'recentAchievements'));
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
        $detailRiwayat = $this->liveDetailRiwayat($live, \App\Http\Controllers\KpiFormController::startUrl('produksi'));

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

