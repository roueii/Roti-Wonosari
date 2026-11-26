<?php

namespace App\Services;

/**
 * Mengolah GET /api/my-kpis (Backend) menjadi ringkasan dashboard karyawan.
 *
 * Catatan: Backend mengelompokkan indikator per KATEGORI KPI (mis. "Pengiriman & Kendaraan"),
 * bukan per langkah form Figma. Karena itu label progres di dashboard mengikuti kategori Backend.
 */
class KpiDashboardService
{
    private const DONE = ['SUBMITTED', 'APPROVED'];

    /**
     * @return array{summary: array, groups: array<int, array>}|null  null jika Backend tidak tersedia / kosong
     */
    public static function myKpis(): ?array
    {
        // Utama: GET /api/dashboard/me (ringkasan resmi Backend, sudah dikelompokkan); cadangan: GET /api/my-kpis
        $rows = [];
        $me = BackendApiService::fetch('dashboard/me')['data']['groups'] ?? null;
        if (is_array($me)) {
            foreach ($me as $g) {
                foreach ($g['indicators'] ?? [] as $ind) {
                    $rows[] = ['category' => $g['label'] ?? 'Lainnya', 'status' => $ind['status'] ?? null];
                }
            }
        } else {
            foreach (BackendApiService::fetch('my-kpis')['data'] ?? [] as $pk) {
                $rows[] = ['category' => $pk['indicator']['category']['name'] ?? 'Lainnya', 'status' => $pk['submission']['status'] ?? null];
            }
        }

        if (! $rows) {
            return null;
        }

        $groups = [];
        foreach ($rows as $row) {
            $category = $row['category'];
            $status = $row['status'];

            $groups[$category] ??= ['name' => $category, 'completed' => 0, 'total' => 0, 'rejected' => 0];
            $groups[$category]['total']++;
            if (in_array($status, self::DONE, true)) {
                $groups[$category]['completed']++;
            }
            if ($status === 'REJECTED') {
                $groups[$category]['rejected']++;
            }
        }

        $groups = array_values(array_map(function ($g) {
            $g['percentage'] = $g['total'] ? (int) round($g['completed'] / $g['total'] * 100) : 0;

            if ($g['rejected'] > 0) {
                [$g['status'], $g['status_label'], $g['badge_type']] = ['tidak_tercapai', 'Tidak tercapai', 'danger'];
            } elseif ($g['completed'] >= $g['total']) {
                [$g['status'], $g['status_label'], $g['badge_type']] = ['tercapai', 'Tercapai', 'success'];
            } else {
                [$g['status'], $g['status_label'], $g['badge_type']] = ['belum_diisi', 'Belum diisi', 'warning'];
            }

            return $g;
        }, $groups));

        $completed = array_sum(array_column($groups, 'completed'));
        $total = array_sum(array_column($groups, 'total'));

        return [
            'summary' => [
                'completed' => $completed,
                'total' => $total,
                'label' => $completed . '/' . $total,
                'status_text' => 'Tuntas',
                'percentage' => $total ? (int) round($completed / $total * 100) : 0,
            ],
            'groups' => $groups,
        ];
    }

    /**
     * Semua data dashboard karyawan dari Backend (my-kpis + riwayat), dalam bentuk yang dipakai
     * view dashboard semua role. Bila belum ada data, nilainya kosong (bukan data contoh).
     *
     * @param  string  $startUrl     URL langkah pertama Isi Capaian
     * @param  string  $detailRoute  nama route detail riwayat (menerima ?id=Ymd)
     */
    public static function forDashboard(string $startUrl, string $detailRoute): array
    {
        $live = self::myKpis();
        $groups = $live['groups'] ?? [];
        $summary = $live['summary'] ?? ['completed' => 0, 'total' => 0, 'label' => '0/0', 'status_text' => 'Tuntas', 'percentage' => 0];

        $previewItems = array_map(fn ($g) => [
            'title' => $g['name'],
            'status' => $g['status'],
            'status_label' => $g['status_label'],
            'badge_type' => $g['badge_type'],
            'route' => $startUrl,
            'url' => $startUrl,
        ], $groups);

        $badge = ['disetujui' => 'badge-green', 'menunggu' => 'badge-amber', 'revisi' => 'badge-red'];
        $recent = array_map(function ($r) use ($detailRoute, $badge) {
            $url = route($detailRoute, ['id' => $r['id']]);

            return [
                'id' => $r['id'],
                'title' => 'Riwayat ' . \Carbon\Carbon::parse($r['date'])->locale('id')->translatedFormat('j F Y'),
                'sub' => $r['checklist_text'],
                'status' => $r['status'],
                'status_label' => $r['status_label'],
                'badge_class' => $badge[$r['status']] ?? 'badge-amber',
                'route' => $url,
                'url' => $url,
            ];
        }, array_slice(KpiHistoryService::list()['items'] ?? [], 0, 3));

        return [
            'metricsSummary' => $summary + [
                'indicators' => array_map(fn ($g) => [
                    'name' => $g['name'],
                    'completed' => $g['completed'],
                    'total' => $g['total'],
                    'percentage' => $g['percentage'],
                ], $groups),
            ],
            'progressRows' => array_map(fn ($g) => [
                'title' => $g['name'],
                'current' => $g['completed'],
                'target' => $g['total'],
                'percent' => $g['percentage'],
            ], $groups),
            'dailyTask' => [
                'banner_title' => 'Mulai Isi Capaian Hari Ini',
                'banner_sub' => count($groups) . ' kategori · 5–10 menit',
                'start_route' => $startUrl,
                'preview_items' => $previewItems,
            ],
            'recentAchievements' => $recent,
            'kpiUnavailable' => $live === null,
        ];
    }

    /**
     * Profil user dari Backend (GET /api/me) digabung ke data default.
     */
    public static function mergeProfile(array $user): array
    {
        $me = BackendApiService::fetch('me');
        if (! $me) {
            return $user;
        }

        return array_merge($user, array_filter([
            'name' => $me['name'] ?? null,
            'email' => $me['email'] ?? null,
        ]));
    }
}
