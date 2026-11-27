<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use App\Services\KpiBackendSubmitter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Form Isi Capaian untuk semua role karyawan, dibuat otomatis dari indikator jabatan di Backend
 * (GET /api/my-kpis). Apa pun yang diatur Admin di Kelola KPI langsung tampil di sini:
 *  - satu langkah = satu kelompok (kategori) indikator,
 *  - satu isian = satu indikator: ceklis (Ya/Tidak) atau angka (dengan satuan & target),
 *  - indikator "wajib foto" meminta foto bukti di langkahnya.
 * Jawaban per langkah disimpan di sesi, lalu dikirim sekaligus di langkah terakhir.
 */
class KpiFormController extends Controller
{
    /** role => [nama route, route dashboard, label, badge] */
    public const ROLES = [
        'frontliner' => ['frontliner', 'staff.dashboard', 'Frontliner', 'FRONTLINER'],
        'driver' => ['driver', 'driver.dashboard', 'Driver', 'DRIVER'],
        'produksi' => ['produksi', 'produksi.dashboard', 'Produksi', 'PRODUKSI'],
        'store_manager' => ['storemanager', 'storemanager.dashboard', 'Store Manager', 'STORE MANAGER'],
        'sales_marketing' => ['sales', 'sales.dashboard', 'Sales & Marketing', 'SALES & MARKETING'],
        'supervisor' => ['supervisor', 'supervisor.dashboard', 'Supervisor', 'SUPERVISOR'],
    ];

    private const LOCKED = ['SUBMITTED', 'APPROVED'];

    /** URL langkah pertama form untuk sebuah role (dipakai dashboard, menu, dan tombol "Perbaiki"). */
    public static function startUrl(string $role): string
    {
        return route(self::ROLES[$role][0] . '.kpi');
    }

    /** Role dari nama route, mis. "driver.kpi.post" -> driver. */
    private function role(Request $request): string
    {
        $prefix = strtok((string) $request->route()->getName(), '.');
        $role = collect(self::ROLES)->search(fn ($r) => $r[0] === $prefix);
        abort_unless($role, 404);

        return $role;
    }

    private function sessionKey(string $role, string $suffix = ''): string
    {
        return "kpi_form.{$role}" . ($suffix !== '' ? ".{$suffix}" : '');
    }

    /**
     * Langkah form dari Backend: [slug => [title, items => [key => indikator]]].
     *
     * @return array{0: array<string, array>, 1: array<string, array>}|null  [langkah, KPI per kode]; null = Backend gagal
     */
    private function steps(): ?array
    {
        $kpis = BackendApiService::fetch('my-kpis')['data'] ?? null;
        if (! is_array($kpis)) {
            return null;
        }

        usort($kpis, fn ($a, $b) => [($a['indicator']['category']['id'] ?? 0), -$a['weight']] <=> [($b['indicator']['category']['id'] ?? 0), -$b['weight']]);

        $steps = [];
        $byCode = [];
        foreach ($kpis as $pk) {
            $ind = $pk['indicator'];
            $cat = $ind['category']['name'] ?? 'Lainnya';
            $slug = Str::slug($cat) ?: 'lainnya';
            $sub = $pk['submission'] ?? null;
            $byCode[$ind['code']] = $pk;

            $steps[$slug] ??= ['title' => $cat, 'items' => []];
            $steps[$slug]['items']['k' . $pk['id']] = [
                'position_kpi_id' => $pk['id'],
                'code' => $ind['code'],
                'title' => $ind['name'],
                'desc' => $ind['description'] ?? '',
                'type' => $pk['input_type'] ?? $ind['assessment_type'] ?? 'YES_NO',
                'unit' => $ind['unit'] ?? '',
                'target' => (float) $pk['target'],
                'lower_better' => ($pk['calculation_type'] ?? '') === 'LOWER_IS_BETTER',
                'requires_evidence' => (bool) ($pk['requires_evidence'] ?? $ind['requires_evidence'] ?? false),
                'frequency' => $pk['frequency'] ?? 'DAILY',
                'status' => $sub['status'] ?? null,
                'locked' => in_array($sub['status'] ?? null, self::LOCKED, true),
                'evidences' => (int) ($sub['evidences_count'] ?? 0),
                'review_note' => ($sub['status'] ?? null) === 'REJECTED' ? ($sub['review']['notes'] ?? null) : null,
                // Jawaban tersimpan di Backend (draf / ditolak / terkirim)
                'saved' => ($pk['input_type'] ?? 'YES_NO') === 'YES_NO'
                    ? ($sub['answer_yes_no'] ?? null)
                    : ($sub['numeric_value'] ?? null),
            ];
        }

        return [$steps, $byCode];
    }

    public function step(Request $request, ?string $step = null)
    {
        $role = $this->role($request);
        [$routeName, $dashboard, $label, $badge] = self::ROLES[$role];
        $cfg = ['page_title' => $label, 'badge' => $badge];
        $user = $this->profileUser(['name' => 'User']);

        $data = $this->steps();
        if ($data === null || ! $data[0]) {
            return view('kpi-form.empty', compact('cfg', 'user') + [
                'dashboardUrl' => route($dashboard),
                'message' => $data === null
                    ? 'Data KPI tidak dapat dimuat. Server sedang tidak dapat dihubungi, coba lagi beberapa saat lagi.'
                    : 'Belum ada hal yang perlu dicatat untuk bagian Anda. Hubungi Admin agar mengatur KPI di menu Kelola KPI.',
            ]);
        }

        [$steps] = $data;
        $slugs = array_keys($steps);
        if ($step === null || ! isset($steps[$step])) {
            return redirect()->route("{$routeName}.kpi", $slugs[0]);
        }

        $index = array_search($step, $slugs, true);
        $answers = session($this->sessionKey($role, $step), []);

        $items = $steps[$step]['items'];
        foreach ($items as $key => &$item) {
            // Urutan nilai: isian di sesi ini -> jawaban tersimpan di Backend -> kosong
            $item['value'] = array_key_exists($key, $answers) ? $answers[$key] : $item['saved'];
            $item['stashed'] = KpiBackendSubmitter::stashedCount($item['code']);
        }
        unset($item);

        $needsPhoto = collect($items)->contains(fn ($i) => $i['requires_evidence'] && ! $i['locked']);

        return view('kpi-form.step', compact('cfg', 'user', 'items', 'step', 'needsPhoto') + [
            'title' => $steps[$step]['title'],
            'stepNumber' => $index + 1,
            'totalSteps' => count($slugs),
            'isLast' => $index === count($slugs) - 1,
            'prevUrl' => $index > 0 ? route("{$routeName}.kpi", $slugs[$index - 1]) : null,
            'postUrl' => route("{$routeName}.kpi.post", $step),
            'draftUrl' => route("{$routeName}.kpi.draft"),
            'dashboardUrl' => route($dashboard),
            'allLocked' => collect($items)->every(fn ($i) => $i['locked']),
        ]);
    }

    public function save(Request $request, string $step)
    {
        $role = $this->role($request);
        [$routeName, $dashboard] = self::ROLES[$role];

        $data = $this->steps();
        abort_unless($data && isset($data[0][$step]), 404);
        [$steps, $byCode] = $data;

        $request->validate([
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photos.max' => 'Maksimal 5 foto per langkah.',
            'photos.*.image' => 'File harus berupa foto (JPG/PNG).',
            'photos.*.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        [$answers, $errors] = $this->readAnswers($request, $steps[$step]['items']);
        session([$this->sessionKey($role, $step) => $answers]);

        // Foto bukti: diteruskan ke setiap indikator wajib foto di langkah ini
        foreach ($steps[$step]['items'] as $item) {
            if ($item['requires_evidence'] && ! $item['locked']) {
                KpiBackendSubmitter::stashPhotos($request, $item['code']);
                if ($item['evidences'] === 0 && KpiBackendSubmitter::stashedCount($item['code']) === 0) {
                    $errors[] = "Unggah foto bukti untuk \"{$item['title']}\" sebelum lanjut.";
                }
            }
        }

        if ($errors) {
            return redirect()->route("{$routeName}.kpi", $step)->withErrors(['step' => $errors[0]]);
        }

        $slugs = array_keys($steps);
        $index = array_search($step, $slugs, true);
        if ($index < count($slugs) - 1) {
            return redirect()->route("{$routeName}.kpi", $slugs[$index + 1]);
        }

        // Langkah terakhir: semua langkah dengan isian yang belum terkirim harus sudah disimpan
        foreach ($slugs as $i => $slug) {
            $open = collect($steps[$slug]['items'])->reject(fn ($it) => $it['locked']);
            if ($open->isNotEmpty() && session($this->sessionKey($role, $slug)) === null) {
                return redirect()->route("{$routeName}.kpi", $slug)->withErrors([
                    'step' => 'Langkah ' . ($i + 1) . ' (' . $steps[$slug]['title'] . ') belum disimpan. Lengkapi lalu lanjutkan sampai langkah terakhir.',
                ]);
            }
        }

        $res = KpiBackendSubmitter::send($this->payload($role, $steps), $byCode, true);
        if (! $res['ok']) {
            return redirect()->route("{$routeName}.kpi", $step)->withErrors(['step' => $res['first_error'] ?? $res['message']]);
        }

        session()->forget($this->sessionKey($role));

        return redirect()->route($dashboard)->with('status', 'Seluruh capaian berhasil dikirim dan menunggu ditinjau.');
    }

    /** Simpan langkah yang sedang dibuka sebagai draf (di sesi dan di Backend). */
    public function draft(Request $request)
    {
        $role = $this->role($request);
        [, $dashboard] = self::ROLES[$role];
        $step = (string) $request->input('step');

        $data = $this->steps();
        abort_unless($data && isset($data[0][$step]), 422);
        [$steps, $byCode] = $data;

        [$answers] = $this->readAnswers($request, $steps[$step]['items']);
        session([$this->sessionKey($role, $step) => $answers]);

        // Simpan semua yang sudah diisi (langkah ini + langkah sebelumnya yang sudah di-"Lanjut")
        $items = $this->payload($role, $steps, true);
        $res = $items ? KpiBackendSubmitter::send($items, $byCode, false) : ['ok' => true];

        return response()->json([
            'success' => $res['ok'],
            'message' => $res['ok']
                ? 'Capaian berhasil disimpan sebagai draf sementara!'
                : 'Draf tersimpan di perangkat ini, tetapi gagal disimpan ke Backend: ' . ($res['first_error'] ?? $res['message']),
            'redirect' => route($dashboard),
        ]);
    }

    /**
     * Baca jawaban satu langkah dari request.
     *
     * @return array{0: array<string, bool|float|null>, 1: array<int, string>}  [jawaban, pesan error]
     */
    private function readAnswers(Request $request, array $items): array
    {
        $input = (array) $request->input('answers', []);
        $answers = [];
        $errors = [];

        foreach ($items as $key => $item) {
            if ($item['locked']) {
                continue;
            }
            if ($item['type'] === 'YES_NO') {
                $answers[$key] = ! empty($input[$key]);
                continue;
            }

            // <input type="number"> selalu mengirim format standar (titik desimal); koma diterima untuk jaga-jaga
            $raw = str_replace(',', '.', trim((string) ($input[$key] ?? '')));
            if ($raw === '') {
                $answers[$key] = null;
                $errors[] = "Isi angka untuk \"{$item['title']}\".";
            } elseif (! is_numeric($raw) || (float) $raw < 0) {
                $answers[$key] = null;
                $errors[] = "Angka untuk \"{$item['title']}\" tidak valid.";
            } else {
                $answers[$key] = (float) $raw;
            }
        }

        return [$answers, $errors];
    }

    /** Susun item kiriman Backend dari jawaban di sesi (hanya indikator yang belum terkirim). */
    private function payload(string $role, array $steps, bool $skipEmpty = false): array
    {
        $items = [];
        foreach ($steps as $slug => $s) {
            $answers = session($this->sessionKey($role, $slug), []);
            foreach ($s['items'] as $key => $item) {
                if ($item['locked'] || ! array_key_exists($key, $answers)) {
                    continue;
                }
                if ($item['type'] === 'YES_NO') {
                    $items[] = ['position_kpi_id' => $item['position_kpi_id'], 'answer_yes_no' => (bool) $answers[$key]];
                } elseif ($answers[$key] !== null || ! $skipEmpty) {
                    $items[] = ['position_kpi_id' => $item['position_kpi_id'], 'numeric_value' => $answers[$key]];
                }
            }
        }

        return $items;
    }
}
