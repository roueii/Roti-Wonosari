<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\KpiHistoryService;
use App\Services\KpiDashboardService;
use App\Services\BackendApiService;

class StoreManagerController extends Controller
{
    /**
     * Item ceklis Store Manager (Figma) -> kode indikator Backend (posisi Store Manager).
     * CLN_STORE dan HR_TEAM_ATTEND belum punya item di form Figma sehingga tidak dikirim dari sini.
     */
    private const BACKEND_MAP = [
        'target_omset' => 'SAL_TARGET',
        'pertumbuhan_omset' => 'SAL_GROWTH',
        'average_basket' => 'SAL_BASKET',
        'food_cost' => 'FIN_FOODCOST',
        'waste_expired' => 'FIN_WASTE',
        'akurasi_stok' => 'INV_ACCURACY',
        'stock_opname' => 'INV_OPNAME',
        'kepuasan_pelanggan' => 'SVC_CSAT',
        'komplain_selesai' => 'SVC_COMPLAINT',
        'display_lengkap' => 'OPS_DISPLAY',
        'sop_buka' => 'OPS_SOP',
        'sop_tutup' => 'OPS_SOP',
        'briefing_harian' => 'HR_BRIEFING',
        'training_karyawan' => 'HR_TRAINING',
        'promo_terjadwal' => 'OPS_PROMO',
        'promo_dieksekusi' => 'OPS_PROMO',
    ];

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
        $data = KpiDashboardService::forDashboard(route('storemanager.penjualan'), 'storemanager.riwayat.detail');
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
    // MULTI-STEP PENGISIAN CAPAIAN STORE MANAGER (5 LANGKAH SESUAI FIGMA)
    // =========================================================================

    /**
     * Langkah 1 dari 5: Penjualan
     */
    public function isiPenjualan()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 1,
            'total' => 5,
            'title' => 'Penjualan',
            'subtitle' => 'Centang indikator yang tercapai hari ini',
            'category_name' => 'Penjualan',
            'next_route' => route('storemanager.laba_persediaan'),
            'back_route' => route('storemanager.dashboard'),
        ];

        // Definisi item: config/kpi_form.php (semua ceklis mulai kosong)
        $indicators = config('kpi_form.store_manager.penjualan');

        return view('storemanager.penjualan', compact('user', 'stepInfo', 'indicators'));
    }

    /**
     * Simpan Langkah 1: Penjualan
     */
    public function simpanPenjualan(Request $request)
    {
        session([
            'sm_penjualan' => [
                'indicators' => $request->input('indicators', []),
                'saved_at' => now()->toDateTimeString(),
            ]
        ]);

        return redirect()->route('storemanager.laba_persediaan')->with('status', 'Penjualan berhasil disimpan!');
    }

    /**
     * Langkah 2 dari 5: Laba & Persediaan
     */
    public function isiLabaPersediaan()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 2,
            'total' => 5,
            'title' => 'Laba & Persediaan',
            'subtitle' => 'Centang indikator yang tercapai hari ini',
            'category_name' => 'Laba & Persediaan',
            'next_route' => route('storemanager.pelayanan'),
            'back_route' => route('storemanager.penjualan'),
        ];

        $labaBiayaIndicators = config('kpi_form.store_manager.laba_persediaan.laba_biaya');

        $persediaanIndicators = config('kpi_form.store_manager.laba_persediaan.persediaan');

        return view('storemanager.laba_persediaan', compact('user', 'stepInfo', 'labaBiayaIndicators', 'persediaanIndicators'));
    }

    /**
     * Simpan Langkah 2: Laba & Persediaan
     */
    public function simpanLabaPersediaan(Request $request)
    {
        session([
            'sm_laba_persediaan' => [
                'laba' => $request->input('laba', []),
                'persediaan' => $request->input('persediaan', []),
                'saved_at' => now()->toDateTimeString(),
            ]
        ]);

        return redirect()->route('storemanager.pelayanan')->with('status', 'Laba & Persediaan berhasil disimpan!');
    }

    /**
     * Langkah 3 dari 5: Pelayanan
     */
    public function isiPelayanan()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 3,
            'total' => 5,
            'title' => 'Pelayanan',
            'subtitle' => 'Centang indikator yang tercapai hari ini',
            'category_name' => 'Pelayanan',
            'next_route' => route('storemanager.operasional_sdm'),
            'back_route' => route('storemanager.laba_persediaan'),
        ];

        // Definisi item: config/kpi_form.php (semua ceklis mulai kosong)
        $indicators = config('kpi_form.store_manager.pelayanan');

        return view('storemanager.pelayanan', compact('user', 'stepInfo', 'indicators'));
    }

    /**
     * Simpan Langkah 3: Pelayanan
     */
    public function simpanPelayanan(Request $request)
    {
        session([
            'sm_pelayanan' => [
                'indicators' => $request->input('indicators', []),
                'saved_at' => now()->toDateTimeString(),
            ]
        ]);

        return redirect()->route('storemanager.operasional_sdm')->with('status', 'Pelayanan berhasil disimpan!');
    }

    /**
     * Langkah 4 dari 5: Operasional & SDM
     */
    public function isiOperasionalSdm()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 4,
            'total' => 5,
            'title' => 'Operasional & SDM',
            'subtitle' => 'Centang indikator yang tercapai hari ini',
            'category_name' => 'Operasional & SDM',
            'next_route' => route('storemanager.promosi_event'),
            'back_route' => route('storemanager.pelayanan'),
        ];

        $operasionalIndicators = config('kpi_form.store_manager.operasional_sdm.operasional');

        $sdmIndicators = config('kpi_form.store_manager.operasional_sdm.sdm');

        $existingPhotos = [];

        return view('storemanager.operasional_sdm', compact('user', 'stepInfo', 'operasionalIndicators', 'sdmIndicators', 'existingPhotos'));
    }

    /**
     * Simpan Langkah 4: Operasional & SDM
     */
    public function simpanOperasionalSdm(Request $request)
    {
        \App\Services\KpiBackendSubmitter::stashPhotos($request, 'OPS_SOP');

        session([
            'sm_operasional_sdm' => [
                'operasional' => $request->input('operasional', []),
                'sdm' => $request->input('sdm', []),
                'photos' => $request->input('photos', []),
                'saved_at' => now()->toDateTimeString(),
            ]
        ]);

        return redirect()->route('storemanager.promosi_event')->with('status', 'Operasional & SDM berhasil disimpan!');
    }

    /**
     * Langkah 5 dari 5: Promosi & Event (Final Submit)
     */
    public function isiPromosiEvent()
    {
        $user = $this->currentUser();

        $stepInfo = [
            'current' => 5,
            'total' => 5,
            'title' => 'Promosi & Event',
            'subtitle' => 'Centang indikator yang tercapai hari ini',
            'category_name' => 'Promosi & Event',
            'back_route' => route('storemanager.operasional_sdm'),
        ];

        // Definisi item: config/kpi_form.php (semua ceklis mulai kosong)
        $indicators = config('kpi_form.store_manager.promosi_event');

        $existingPhotos = [];

        // Pulihkan centang dari draf / data tersimpan sebelumnya
        $saved = session('sm_promosi_event') ?? session('sm_capaian_draft');
        if (is_array($saved)) {
            foreach ($indicators as &$indicator) {
                $indicator['checked'] = !empty($saved['indicators'][$indicator['id']]);
            }
            unset($indicator);
        }

        return view('storemanager.promosi_event', compact('user', 'stepInfo', 'indicators', 'existingPhotos'));
    }

    /**
     * Simpan Langkah 5: Promosi & Event (Final Submit)
     */
    public function simpanPromosiEvent(Request $request)
    {
        \App\Services\KpiBackendSubmitter::stashPhotos($request, 'OPS_PROMO');

        // Kirim ke Backend: gabungan item yang dicentang di kelima langkah
        $checked = array_merge(
            $this->checkedKeys(session('sm_penjualan.indicators', [])),
            $this->checkedKeys(session('sm_laba_persediaan.indicators', [])),
            $this->checkedKeys(session('sm_pelayanan.indicators', [])),
            $this->checkedKeys(session('sm_operasional_sdm.operasional', [])),
            $this->checkedKeys(session('sm_operasional_sdm.sdm', [])),
            $this->checkedKeys($request->input('indicators', [])),
        );
        if ($error = $this->kirimCapaianKeBackend($request, self::BACKEND_MAP, $checked, 'storemanager.promosi_event')) {
            return $error;
        }

        session([
            'sm_promosi_event' => [
                'indicators' => $request->input('indicators', []),
                'photos' => $request->input('photos', []),
                'saved_at' => now()->toDateTimeString(),
                'status' => 'submitted',
            ]
        ]);

        return redirect()->route('storemanager.dashboard')->with('status', 'Seluruh capaian Store Manager (5/5 kategori) berhasil dikirim!');
    }

    /**
     * Simpan Draf Sementara
     */
    public function simpanDraft(Request $request)
    {
        session(['sm_capaian_draft' => [
            'indicators' => $request->input('indicators', []),
            'drafted_at' => now()->toDateTimeString(),
        ]]);

        return redirect()->route('storemanager.dashboard')->with('status', 'Draf capaian Store Manager berhasil disimpan.');
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
        [$reportMeta, $revisionNote, $categories] = $this->liveReportDetail($live, route('storemanager.penjualan'));

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

