<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KpiHistoryService;
use App\Services\KpiDashboardService;

class FrontlinerController extends Controller
{
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
        $data = KpiDashboardService::forDashboard(\App\Http\Controllers\KpiFormController::startUrl('frontliner'), 'frontliner.riwayat.detail');
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
        [$reportMeta, $revisionNote, $categories] = $this->liveReportDetail($live, \App\Http\Controllers\KpiFormController::startUrl('frontliner'));

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
