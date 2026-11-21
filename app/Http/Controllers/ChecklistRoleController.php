<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BackendApiService;
use App\Services\KpiBackendSubmitter;
use App\Services\KpiHistoryService;

/**
 * Controller dasar untuk role KPI berbasis checklist multi-langkah
 * (dipakai oleh Sales & Marketing dan Supervisor).
 *
 * Seluruh konten (langkah, indikator, label) didefinisikan sebagai data di
 * method roleConfig() milik subclass, sehingga saat integrasi Backend cukup
 * mengganti sumber data (session dummy -> BackendApiService) tanpa
 * menyentuh Blade.
 */
abstract class ChecklistRoleController extends Controller
{
    /**
     * Konfigurasi role. Struktur:
     * - key, route (prefix nama route), label, badge, drawer_label, help_text
     * - steps: [slug => [title, short_title, preview_title, subtitle, card_title,
     *           tag, card_sub, notice, notice_type, upload?, items => [key => [title, desc]]]]
     */
    abstract protected function roleConfig(): array;

    protected function config(): array
    {
        static $cache = [];
        return $cache[static::class] ??= $this->roleConfig();
    }

    protected function sessionKey(string $suffix = ''): string
    {
        return $this->config()['key'] . '_capaian' . ($suffix !== '' ? '.' . $suffix : '');
    }

    // =========================================================================
    // INTEGRASI BACKEND: kirim capaian ke POST /api/my-kpis/submissions
    // =========================================================================

    /**
     * Pemetaan item checklist Frontend -> KODE indikator Backend (kolom kpi_indicators.code).
     * Beberapa item boleh menunjuk kode yang sama: indikator itu dianggap tercapai (Ya)
     * hanya bila SEMUA item terkaitnya dicentang (indikator Backend bersifat ringkasan).
     * Kosong = capaian role ini belum dikirim ke Backend (tetap disimpan di session).
     *
     * @return array<string, string>  item key => kode indikator
     */
    protected function backendIndicators(): array
    {
        return [];
    }

    /**
     * KPI jabatan user dari GET /api/my-kpis, dikelompokkan per kode indikator.
     *
     * @return array<string, array>|null  kode => position KPI Backend; null jika tidak terhubung / tidak dipetakan
     */
    protected function backendKpis(): ?array
    {
        if (! $this->backendIndicators() || ! BackendApiService::connected()) {
            return null;
        }

        $items = BackendApiService::fetch('my-kpis')['data'] ?? null;
        if (! is_array($items)) {
            return null;
        }

        return collect($items)
            ->filter(fn ($pk) => ! empty($pk['indicator']['code']))
            ->keyBy(fn ($pk) => $pk['indicator']['code'])
            ->all();
    }

    /**
     * Kode indikator Backend yang menerima foto/lampiran dari langkah $step (null = tidak diteruskan).
     */
    protected function photoCode(string $step): ?string
    {
        return null;
    }

    /**
     * Kirim capaian ke Backend lewat KpiBackendSubmitter (agregasi item -> indikator + foto bukti).
     *
     * @return array|null  hasil BackendApiService::send, atau null jika role/sesi tidak terhubung ke Backend
     */
    protected function sendToBackend(array $checkedKeys, array $keys, bool $submit): ?array
    {
        return $keys
            ? KpiBackendSubmitter::submit($this->backendIndicators(), $checkedKeys, $submit, $keys)
            : null;
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

    /**
     * Data bersama untuk semua view role checklist.
     */
    protected function viewData(array $extra = []): array
    {
        return array_merge([
            'cfg' => $this->config(),
            'user' => $this->currentUser(),
        ], $extra);
    }

    /**
     * Hitung progres tiap langkah dari isian di session (belum diisi = 0).
     */
    protected function categoryProgress(): array
    {
        $cfg = $this->config();
        $saved = session($this->sessionKey(), []);
        $result = [];

        foreach ($cfg['steps'] as $slug => $step) {
            $total = count($step['items']);

            if (isset($saved[$slug])) {
                $completed = count(array_intersect(array_keys($saved[$slug]['indicators'] ?? []), array_keys($step['items'])));
                $status = $completed >= $total ? 'tercapai' : 'tidak_tercapai';
            } else {
                [$completed, $status] = [0, 'belum_diisi'];
            }

            $result[] = [
                'slug' => $slug,
                'name' => $step['short_title'] ?? $step['title'],
                'preview_title' => $step['preview_title'] ?? ($step['short_title'] ?? $step['title']),
                'completed' => $completed,
                'total' => $total,
                'percentage' => $total > 0 ? round($completed / $total * 100) : 0,
                'status' => $status,
                'url' => route($cfg['route'] . '.step', $slug),
            ];
        }

        return $result;
    }

    public function dashboard()
    {
        $cfg = $this->config();
        $categories = $this->categoryProgress();

        $completed = array_sum(array_column($categories, 'completed'));
        $total = array_sum(array_column($categories, 'total'));

        $summary = [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? round($completed / $total * 100) : 0,
        ];

        $firstStep = array_key_first($cfg['steps']);

        // Data nyata dari Backend (GET /api/my-kpis) jika login lewat Backend.
        // Kategori mengikuti Backend, bukan langkah form Figma.
        if ($live = \App\Services\KpiDashboardService::myKpis()) {
            $summary = $live['summary'];
            $categories = array_map(fn ($g) => [
                'slug' => null,
                'name' => $g['name'],
                'preview_title' => $g['name'],
                'completed' => $g['completed'],
                'total' => $g['total'],
                'percentage' => $g['percentage'],
                'status' => $g['status'],
                'url' => route($cfg['route'] . '.step', $firstStep),
            ], $live['groups']);
        }

        return view('checklist.dashboard', $this->viewData([
            'todayBadge' => 'Hari ini, ' . now()->locale('id')->translatedFormat('j M'),
            'summary' => $summary,
            'categories' => $categories,
            'startUrl' => route($cfg['route'] . '.step', $firstStep),
            'recentAchievements' => \App\Services\KpiDashboardService::forDashboard(
                route($cfg['route'] . '.step', $firstStep),
                $cfg['route'] . '.riwayat.detail'
            )['recentAchievements'],
        ]));
    }

    /**
     * Tampilkan langkah pengisian capaian.
     */
    public function step(string $step)
    {
        $cfg = $this->config();
        abort_unless(isset($cfg['steps'][$step]), 404);

        $slugs = array_keys($cfg['steps']);
        $index = array_search($step, $slugs, true);

        $saved = session($this->sessionKey($step), []);
        $draft = session($this->sessionKey('draft.' . $step), []);

        // Belum ada isian di sesi ini: pakai jawaban yang sudah tersimpan di Backend (draft/terkirim)
        if (! isset($saved['indicators']) && ! isset($draft['indicators']) && ($kpis = $this->backendKpis())) {
            // Indikator ringkasan bernilai Ya = semua item pemetaannya dicentang
            $map = $this->backendIndicators();
            $fromBackend = array_filter(
                $cfg['steps'][$step]['items'],
                fn ($item, $key) => isset($map[$key])
                    && ($kpis[$map[$key]]['submission']['answer_yes_no'] ?? false) === true,
                ARRAY_FILTER_USE_BOTH
            );
            if ($fromBackend) {
                $draft = ['indicators' => array_map(fn () => true, $fromBackend)];
            }
        }

        return view('checklist.step', $this->viewData([
            'slug' => $step,
            'step' => $cfg['steps'][$step],
            'stepNumber' => $index + 1,
            'totalSteps' => count($slugs),
            'isFirst' => $index === 0,
            'isLast' => $index === count($slugs) - 1,
            'prevUrl' => $index > 0 ? route($cfg['route'] . '.step', $slugs[$index - 1]) : null,
            'checked' => $saved['indicators'] ?? ($draft['indicators'] ?? []),
            'attachments' => $saved['attachments'] ?? [],
        ]));
    }

    /**
     * Simpan langkah, lanjut ke langkah berikutnya atau kirim semua.
     */
    public function saveStep(Request $request, string $step)
    {
        $cfg = $this->config();
        abort_unless(isset($cfg['steps'][$step]), 404);

        $request->validate([
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $indicators = array_intersect_key(
            (array) $request->input('indicators', []),
            $cfg['steps'][$step]['items']
        );

        $attachments = collect($request->file('attachments', []))
            ->map(fn ($file) => $file->getClientOriginalName())
            ->values()
            ->all();

        if ($code = $this->photoCode($step)) {
            KpiBackendSubmitter::stashPhotos($request, $code, 'attachments');
        }

        session([$this->sessionKey($step) => [
            'indicators' => array_map(fn () => true, $indicators),
            'attachments' => $attachments,
            'saved_at' => now()->toDateTimeString(),
        ]]);
        session()->forget($this->sessionKey('draft.' . $step));

        $slugs = array_keys($cfg['steps']);
        $index = array_search($step, $slugs, true);

        if ($index < count($slugs) - 1) {
            return redirect()->route($cfg['route'] . '.step', $slugs[$index + 1]);
        }

        // Pastikan semua langkah sebelumnya sudah disimpan sebelum mengirim
        $saved = session($this->sessionKey(), []);
        foreach ($slugs as $i => $slug) {
            if (!isset($saved[$slug])) {
                return redirect()->route($cfg['route'] . '.step', $slug)->withErrors([
                    'step' => 'Langkah ' . ($i + 1) . ' (' . $cfg['steps'][$slug]['title'] . ') belum disimpan. Lengkapi lalu lanjutkan sampai langkah terakhir.',
                ]);
            }
        }

        // Kirim ke Backend (hanya role yang itemnya dipetakan 1:1 ke indikator Backend)
        $checkedKeys = [];
        foreach ($slugs as $slug) {
            $checkedKeys = array_merge($checkedKeys, array_keys($saved[$slug]['indicators'] ?? []));
        }
        $res = $this->sendToBackend($checkedKeys, array_keys($this->backendIndicators()), true);
        if ($res && ! $res['ok']) {
            return redirect()->route($cfg['route'] . '.step', $step)
                ->withErrors(['step' => $res['first_error'] ?? $res['message']]);
        }

        session([$this->sessionKey('_completed_at') => now()->toDateTimeString()]);

        return redirect()->route($cfg['route'] . '.dashboard')
            ->with('status', 'Seluruh capaian harian berhasil dikirim!');
    }

    /**
     * Simpan draf sementara (AJAX) untuk langkah yang sedang dibuka.
     */
    public function draft(Request $request)
    {
        $cfg = $this->config();
        $step = (string) $request->input('step');
        abort_unless(isset($cfg['steps'][$step]), 422);

        $indicators = array_intersect_key(
            (array) $request->input('indicators', []),
            $cfg['steps'][$step]['items']
        );

        session([$this->sessionKey('draft.' . $step) => [
            'indicators' => array_map(fn () => true, $indicators),
            'drafted_at' => now()->toDateTimeString(),
        ]]);

        // Simpan juga sebagai DRAFT di Backend (jika role dipetakan & login lewat Backend)
        $res = $this->sendToBackend(
            array_keys($indicators),
            array_values(array_intersect(array_keys($this->backendIndicators()), array_keys($cfg['steps'][$step]['items']))),
            false
        );
        if ($res && ! $res['ok']) {
            return response()->json([
                'success' => false,
                'message' => 'Draf tersimpan di perangkat ini, tetapi gagal disimpan ke Backend: ' . ($res['first_error'] ?? $res['message']),
                'redirect' => route($cfg['route'] . '.dashboard'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Capaian berhasil disimpan sebagai draf sementara!',
            'redirect' => route($cfg['route'] . '.dashboard'),
        ]);
    }

    public function riwayat(Request $request)
    {
        // Riwayat dari Backend (GET /api/my-kpis/history); kosong bila belum ada / gagal dimuat
        $history = KpiHistoryService::list();
        $list = $history['items'] ?? [];
        $summaryCounts = $history['summary'] ?? ['disetujui' => 0, 'menunggu' => 0, 'revisi' => 0, 'semua' => 0];

        $filter = in_array($request->query('status'), ['semua', 'disetujui', 'menunggu', 'revisi'], true)
            ? $request->query('status')
            : 'semua';

        return view('checklist.riwayat', $this->viewData([
            'riwayatList' => $list,
            'summaryCounts' => $summaryCounts,
            'filter' => $filter,
        ]));
    }

    public function detailRiwayat(Request $request, $id)
    {
        // Data nyata dari Backend (GET /api/my-kpis/history/{date}); id = tanggal laporan (Ymd)
        if (($date = KpiHistoryService::dateFromId($id)) && ($live = KpiHistoryService::detail($date))) {
            return $this->liveDetailView($live);
        }

        abort(404, 'Laporan tidak ditemukan.');
    }

    /**
     * Detail riwayat dari Backend. Kategori mengikuti Backend (bukan langkah form Figma),
     * jadi tautan kategori / "Perbaiki Capaian" diarahkan ke langkah pertama form.
     */
    protected function liveDetailView(array $live)
    {
        $cfg = $this->config();
        $formUrl = route($cfg['route'] . '.step', array_key_first($cfg['steps']));

        // Nama ikon KpiHistoryService -> alias ikon di checklist/partials/icon.blade.php
        $iconAlias = ['cash' => 'chart', 'dollar' => 'chart', 'sparkles' => 'sparkle', 'shield-check' => 'clipboard', 'truck' => 'box', 'document' => 'clipboard'];

        $categories = array_map(fn ($c) => [
            'title' => $c['name'],
            'icon' => $iconAlias[$c['icon']] ?? $c['icon'],
            'is_highlighted' => $c['needs_revision'],
            'badge' => $c['needs_revision'] ? 'PERLU REVISI' : null,
            'subtitle' => $c['subtitle'],
            'url' => $formUrl,
        ], $live['categories']);

        return view('checklist.detail_riwayat', $this->viewData([
            'item' => [
                'short_date' => $live['short_date'],
                'full_date' => $live['full_date'],
                'sent_label' => $live['sent_label'],
            ],
            'isRevision' => $live['is_revision'],
            'categories' => $categories,
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
