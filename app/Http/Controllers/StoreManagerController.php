<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\KpiHistoryService;
use App\Services\KpiDashboardService;
use App\Services\BackendApiService;

class StoreManagerController extends Controller
{
    protected array $defaultUser = ['name' => 'User', 'role' => 'store_manager', 'role_label' => 'Store Manager', 'email' => 'User@capaian.id'];

    /**
     * Tampilkan halaman utama Dashboard Store Manager (Mendukung Tab "KPI Saya" dan "KPI Tim")
     */
    public function dashboard(Request $request)
    {
        $user = $this->currentUser();

        // Tab aktif: 'saya' atau 'tim'
        $activeTab = $request->query('tab', 'saya');

        // ==========================================
        // DATA TAB: KPI SAYA (Dashboard KPI Store Manager.png)
        // ==========================================
        $todayBadge = 'Hari ini, ' . now()->locale('id')->translatedFormat('j M');

        // KPI Saya: semua data dari Backend (my-kpis + riwayat); kosong bila belum ada data
        $data = KpiDashboardService::forDashboard(\App\Http\Controllers\KpiFormController::startUrl('store_manager'), 'storemanager.riwayat.detail');
        ['metricsSummary' => $metricsSummary, 'progressRows' => $progressRows, 'dailyTask' => $dailyTask, 'recentAchievements' => $recentAchievements] = $data;

        // KPI Tim: diisi dari GET /api/dashboard/team di bawah
        $teamSummary = ['pending_count' => 0, 'approved_count' => 0, 'not_reported_count' => 0, 'reported_text' => '', 'percentage' => '0%', 'percent_val' => 0, 'shift_label' => ''];
        $teamMembers = [];

        // Tab KPI Tim: rekap laporan seluruh tim hari ini (GET /api/dashboard/team)
        $team = BackendApiService::fetch('dashboard/team')['data'] ?? null;
        if (is_array($team) && isset($team['summary'], $team['members'])) {
            $s = $team['summary'];
            $percent = (float) ($s['reported_percent'] ?? 0);
            $teamSummary = array_merge($teamSummary, [
                'shift_label' => Carbon::parse($team['date'] ?? now())->locale('id')->translatedFormat('l, d M Y'),
                'reported_text' => ($s['reported'] ?? 0) . ' dari ' . ($s['total'] ?? 0) . ' karyawan sudah lapor',
                'percentage' => rtrim(rtrim(number_format($percent, 1, '.', ''), '0'), '.') . '%',
                'percent_val' => $percent,
                'pending_count' => (int) ($s['pending'] ?? 0),
                'approved_count' => (int) ($s['approved'] ?? 0),
                'not_reported_count' => (int) ($s['not_reported'] ?? 0),
            ]);

            $badges = [
                'PENDING' => ['approval', 'Approval', 'badge-orange'],
                'APPROVED' => ['disetujui', 'Disetujui', 'badge-green'],
                'REJECTED' => ['revisi', 'Revisi', 'badge-red'],
                'NOT_REPORTED' => ['belum_lapor', 'Belum Lapor', 'badge-gray'],
            ];
            $teamMembers = array_map(function ($m) use ($badges) {
                [$status, $label, $badge] = $badges[$m['status'] ?? ''] ?? ['belum_lapor', 'Belum Lapor', 'badge-gray'];

                return [
                    'id' => $m['user']['id'] ?? null,
                    'name' => $m['user']['name'] ?? '-',
                    'role' => trim(($m['user']['position'] ?? '-') . (! empty($m['user']['position_detail']) ? ' (' . $m['user']['position_detail'] . ')' : '')),
                    'status' => $status,
                    'status_label' => $label,
                    'badge_class' => $badge,
                    'link' => ! empty($m['can_review']) ? route('storemanager.approval') : '#',
                ];
            }, $team['members']);
        } elseif ($pending = $this->pendingReports()) {
            // Cadangan: hanya laporan yang menunggu persetujuan (GET /api/reviews/reports)
            $teamSummary = array_merge($teamSummary, [
                'pending_count' => $pending['total'],
                // Rekap laporan seluruh tim belum ada endpoint-nya di Backend
                'reported_text' => 'Rekap laporan tim belum tersedia dari Backend',
                'percentage' => '–',
                'percent_val' => 0,
                'approved_count' => '–',
                'not_reported_count' => '–',
            ]);
            $teamMembers = array_map(fn ($r) => [
                'id' => $r['user']['id'],
                'name' => $r['user']['name'],
                'role' => ($r['user']['position'] ?? '-') . ' · ' . $r['report_date'],
                'status' => 'approval',
                'status_label' => 'Approval',
                'badge_class' => 'badge-orange',
                'link' => route('storemanager.approval'),
            ], $pending['data']);
        }

        return view('storemanager.dashboard', compact(
            'user',
            'activeTab',
            'todayBadge',
            'metricsSummary',
            'progressRows',
            'dailyTask',
            'recentAchievements',
            'teamSummary',
            'teamMembers'
        ));
    }


    // =========================================================================
    // INTEGRASI BACKEND: APPROVAL TIM (GET/POST /api/reviews/reports)
    // =========================================================================

    /**
     * Laporan karyawan berstatus SUBMITTED yang menunggu keputusan.
     * @return array{total:int, data:array}|null  null jika belum terhubung ke Backend
     */
    protected function pendingReports(): ?array
    {
        $json = BackendApiService::fetch('reviews/reports', ['per_page' => 50]);
        if ($json === null) {
            return null;
        }

        return [
            'total' => (int) ($json['total'] ?? count($json['data'] ?? [])),
            'data' => $json['data'] ?? [],
        ];
    }

    /**
     * Ubah laporan Backend menjadi bentuk kartu di halaman Approval Tim.
     */
    protected function liveApprovalItems(): ?array
    {
        $pending = $this->pendingReports();
        if ($pending === null) {
            return null;
        }

        $categoryOf = fn (?string $position) => match (strtolower((string) $position)) {
            'frontliner' => 'frontliner',
            'produksi' => 'produksi',
            'driver' => 'driver',
            'sales' => 'sales',
            'spv' => 'supervisor',
            default => 'lainnya',
        };

        $items = [];
        foreach ($pending['data'] as $i => $report) {
            $userId = $report['user']['id'];
            $date = $report['report_date'];
            $detail = BackendApiService::fetch("reviews/reports/{$userId}/{$date}")['data'] ?? [];

            $photos = [];
            $rows = [];
            foreach ($detail as $sub) {
                foreach ($sub['evidences'] ?? [] as $ev) {
                    $photos[] = ['title' => $ev['file_name'], 'time' => $sub['indicator']['name'] ?? '', 'url' => $ev['url']];
                }

                $ind = $sub['indicator'] ?? [];
                if (($ind['input_type'] ?? '') === 'YES_NO') {
                    $label = $ind['name'];
                    $met = (bool) $sub['answer_yes_no'];
                } else {
                    $value = $sub['numeric_value'];
                    $target = $ind['target'] ?? 0;
                    $unit = $ind['unit'] ?? '';
                    $label = sprintf('%s: %s %s (target %s)', $ind['name'], $this->fmt($value), $unit, $this->fmt($target));
                    $met = ($ind['calculation_type'] ?? '') === 'LOWER_IS_BETTER' ? $value <= $target : $value >= $target;
                }
                $rows[] = ['label' => $label, 'checked' => $met];
            }

            $metCount = count(array_filter($rows, fn ($r) => $r['checked']));

            $items[] = [
                'id' => $userId . '_' . $date,
                'name' => $report['user']['name'],
                'role' => $report['user']['position'] ?? '-',
                'role_category' => $categoryOf($report['user']['position'] ?? null),
                'submission_meta' => $report['total_indicators'] . ' indikator · ' . \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('j M Y'),
                'status_label' => 'Menunggu Persetujuan Anda',
                'is_expanded' => $i === 0,
                'photos' => $photos,
                'categories' => $rows ? [[
                    'name' => 'INDIKATOR KPI',
                    'ratio' => $metCount . '/' . count($rows) . ' Memenuhi target',
                    'items' => $rows,
                ]] : [],
            ];
        }

        return $items;
    }

    private function fmt($n): string
    {
        $s = number_format((float) $n, 2, ',', '.');
        return str_ends_with($s, ',00') ? substr($s, 0, -3) : $s;
    }

    /**
     * Kirim keputusan satu laporan ke Backend. $id = "{userId}_{YYYY-mm-dd}".
     */
    protected function decideReport(string $id, string $decision, ?string $notes)
    {
        if (! preg_match('/^(\d+)_(\d{4}-\d{2}-\d{2})$/', $id, $m)) {
            return redirect()->route('storemanager.approval')->with('error', 'Laporan tidak dikenali.');
        }

        $res = BackendApiService::send('POST', "reviews/reports/{$m[1]}/{$m[2]}", [
            'decision' => $decision,
            'notes' => $notes ?: null,
        ]);

        return redirect()->route('storemanager.approval')
            ->with($res['ok'] ? 'status' : 'error', $res['ok'] ? $res['message'] : $res['first_error']);
    }

    // =========================================================================
    // HALAMAN TRAINING KARYAWAN (Figma: "Detail Training" & "Card Catat Training Baru")
    // =========================================================================

    /**
     * Daftar training yang dicatat Store Manager ini (Backend belum punya fitur training,
     * jadi disimpan di FE: storage/app/sm_trainings.json, per id akun).
     */
    protected function trainingList(): array
    {
        $list = \App\Services\TrainingService::milik((string) ($this->currentUser()['id'] ?? $this->currentUser()['email'] ?? ''));
        usort($list, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $list;
    }

    /**
     * Halaman Training Karyawan
     */
    public function training()
    {
        $user = $this->currentUser();

        $trainings = array_map(function ($t) {
            $t['date_label'] = \Carbon\Carbon::parse($t['date'])->locale('id')->translatedFormat('j M Y');
            return $t;
        }, $this->trainingList());

        $currentMonth = now()->format('Y-m');
        $monthCount = count(array_filter($trainings, fn ($t) => str_starts_with($t['date'], $currentMonth)));

        $summary = [
            'done' => $monthCount,
            'target' => 1,
        ];

        return view('storemanager.training', compact('user', 'trainings', 'summary'));
    }

    /**
     * Simpan training baru
     */
    public function simpanTraining(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:120'],
            'participants' => ['required', 'integer', 'min:1', 'max:500'],
        ], [], [
            'date' => 'Tanggal Pelaksanaan',
            'topic' => 'Topik Training',
            'participants' => 'Jumlah Peserta',
        ]);

        \App\Services\TrainingService::tambah((string) ($this->currentUser()['id'] ?? $this->currentUser()['email'] ?? ''), [
            'date' => $data['date'],
            'topic' => $data['topic'],
            'participants' => (int) $data['participants'],
        ]);

        return redirect()->route('storemanager.training')->with('status', 'Training berhasil dicatat.');
    }

    // =========================================================================
    // HALAMAN APPROVAL TIM (Halaman Approval Tim.png)
    // =========================================================================

    /**
     * Tampilkan Halaman Approval Tim
     */
    public function approvalTim(Request $request)
    {
        $user = $this->currentUser();

        $filter = $request->query('filter', 'semua');

        // Laporan tim yang menunggu persetujuan, dari Backend (GET /api/reviews/reports)
        $approvalItems = $this->liveApprovalItems() ?? [];

        return view('storemanager.approval', compact('user', 'approvalItems', 'filter'));
    }

    /**
     * Setujui Capaian Karyawan Tunggal
     */
    public function approveCapaian($id)
    {
        return $this->decideReport((string) $id, 'APPROVED', null);
    }

    /**
     * Tolak / Minta Revisi Capaian Karyawan
     */
    public function rejectCapaian(Request $request, $id)
    {
        $catatan = trim((string) $request->input('catatan'));
        if ($catatan === '') {
            return redirect()->route('storemanager.approval')->with('error', 'Alasan penolakan wajib diisi pada kolom catatan.');
        }

        return $this->decideReport((string) $id, 'REJECTED', $catatan);
    }

    /**
     * Setujui Semua Capaian Sekaligus
     */
    public function approveAllCapaian()
    {
        $pending = $this->pendingReports();
        if ($pending === null) {
            return redirect()->route('storemanager.approval')->with('error', 'Data laporan tidak dapat dimuat dari server. Coba lagi.');
        }

        $ok = 0;
        $failed = [];
        foreach ($pending['data'] as $r) {
            $res = BackendApiService::send('POST', "reviews/reports/{$r['user']['id']}/{$r['report_date']}", ['decision' => 'APPROVED']);
            if ($res['ok']) {
                $ok++;
            } else {
                $failed[] = $r['user']['name'];
            }
        }

        if ($failed) {
            return redirect()->route('storemanager.approval')->with('error', "{$ok} laporan disetujui, gagal: " . implode(', ', $failed));
        }

        return redirect()->route('storemanager.approval')->with('status', $ok
            ? "{$ok} laporan berhasil disetujui sekaligus."
            : 'Tidak ada laporan yang menunggu persetujuan.');
    }

    // =========================================================================
    // RIWAYAT & DETAIL RIWAYAT STORE MANAGER
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

        return view('storemanager.riwayat', compact('user', 'summary', 'historyList'));
    }

    /**
     * Halaman Detail Riwayat (Disetujui & Perlu Revisi)
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
        [$reportMeta, $revisionNote, $categories] = $this->liveReportDetail($live, \App\Http\Controllers\KpiFormController::startUrl('store_manager'));

        return view('storemanager.detail_riwayat', compact('user', 'reportMeta', 'revisionNote', 'categories'));
    }

    /**
     * Halaman Profil Store Manager Sesuai Halaman Profil.png
     */
    public function profil()
    {
        $user = $this->profileUser([
            'name' => 'User',
            'status_label' => 'Akun Aktif',
        ]);
        $user['role_label'] = 'STORE MANAGER';

        return view('storemanager.profil', compact('user'));
    }
}

