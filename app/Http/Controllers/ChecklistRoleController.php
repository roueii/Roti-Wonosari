<?php

namespace App\Http\Controllers;

use App\Services\KpiDashboardService;
use App\Services\KpiHistoryService;
use Illuminate\Http\Request;

/**
 * Controller dasar halaman Sales & Marketing dan Supervisor (view bersama "checklist.*"):
 * dashboard, riwayat, detail riwayat, profil. Form Isi Capaian ada di KpiFormController
 * (dibuat otomatis dari Kelola KPI), sama seperti role lain.
 */
abstract class ChecklistRoleController extends Controller
{
    /** Konfigurasi role: key (role FE), route (prefix nama route), label, badge, page_title, help_text. */
    abstract protected function roleConfig(): array;

    protected function config(): array
    {
        static $cache = [];
        return $cache[static::class] ??= $this->roleConfig();
    }

    protected function formUrl(): string
    {
        return KpiFormController::startUrl($this->config()['key']);
    }

    protected function currentUser(): array
    {
        $cfg = $this->config();

        return array_merge($this->profileUser([
            'name' => 'User',
            'status_label' => 'Akun Aktif',
        ]), [
            'role' => $cfg['key'],
            'role_label' => $cfg['label'],
        ]);
    }

    /** Data bersama untuk semua view role checklist. */
    protected function viewData(array $extra = []): array
    {
        return array_merge([
            'cfg' => $this->config(),
            'user' => $this->currentUser(),
            'formUrl' => $this->formUrl(),
        ], $extra);
    }

    public function dashboard()
    {
        $cfg = $this->config();
        $live = KpiDashboardService::myKpis();
        $formUrl = $this->formUrl();

        $summary = $live['summary'] ?? ['completed' => 0, 'total' => 0, 'percentage' => 0];
        $categories = array_map(fn ($g) => [
            'name' => $g['name'],
            'preview_title' => $g['name'],
            'completed' => $g['completed'],
            'total' => $g['total'],
            'percentage' => $g['percentage'],
            'status' => $g['status'],
            'url' => $formUrl,
        ], $live['groups'] ?? []);

        return view('checklist.dashboard', $this->viewData([
            'todayBadge' => 'Hari ini, ' . now()->locale('id')->translatedFormat('j M'),
            'summary' => $summary,
            'categories' => $categories,
            'startUrl' => $formUrl,
            'recentAchievements' => KpiDashboardService::forDashboard($formUrl, $cfg['route'] . '.riwayat.detail')['recentAchievements'],
        ]));
    }

    public function riwayat(Request $request)
    {
        // Riwayat dari Backend (GET /api/my-kpis/history); kosong bila belum ada / gagal dimuat
        $history = KpiHistoryService::list();
        $filter = in_array($request->query('status'), ['semua', 'disetujui', 'menunggu', 'revisi'], true)
            ? $request->query('status')
            : 'semua';

        return view('checklist.riwayat', $this->viewData([
            'riwayatList' => $history['items'] ?? [],
            'summaryCounts' => $history['summary'] ?? ['disetujui' => 0, 'menunggu' => 0, 'revisi' => 0, 'semua' => 0],
            'filter' => $filter,
        ]));
    }

    public function detailRiwayat(Request $request, $id)
    {
        // Detail dari Backend (GET /api/my-kpis/history/{date}); id = tanggal laporan (Ymd)
        $date = KpiHistoryService::dateFromId($id);
        $live = $date ? KpiHistoryService::detail($date) : null;
        abort_unless($live, 404, 'Laporan tidak ditemukan.');

        // Nama ikon KpiHistoryService -> alias ikon di checklist/partials/icon.blade.php
        $iconAlias = ['cash' => 'chart', 'dollar' => 'chart', 'sparkles' => 'sparkle', 'shield-check' => 'clipboard', 'truck' => 'box', 'document' => 'clipboard'];
        $formUrl = $this->formUrl();

        return view('checklist.detail_riwayat', $this->viewData([
            'item' => [
                'short_date' => $live['short_date'],
                'full_date' => $live['full_date'],
                'sent_label' => $live['sent_label'],
            ],
            'isRevision' => $live['is_revision'],
            'categories' => array_map(fn ($c) => [
                'title' => $c['name'],
                'icon' => $iconAlias[$c['icon']] ?? $c['icon'],
                'is_highlighted' => $c['needs_revision'],
                'badge' => $c['needs_revision'] ? 'PERLU REVISI' : null,
                'subtitle' => $c['subtitle'],
                'url' => $formUrl,
            ], $live['categories']),
            'revision' => [
                'requested_by' => $live['revision']['requested_by'] ?? '-',
                'requested_at' => $live['revision']['requested_at'] ?? '',
                'message' => $live['revision']['message'] ?? '',
                'short' => 'Perlu Revisi',
                'repair_url' => $formUrl,
            ],
        ]));
    }

    public function profil()
    {
        return view('checklist.profil', $this->viewData());
    }
}
