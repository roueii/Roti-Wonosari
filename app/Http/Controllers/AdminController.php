<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use App\Services\AksesAkunService;
use App\Services\RekapBulananService;
use App\Services\RevisiTindakLanjutService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected array $defaultUser = ['name' => 'Admin', 'role' => 'admin', 'email' => 'User@capaian.id'];

    /**
     * Dashboard Admin: semua angka dari Backend.
     */
    public function dashboard()
    {
        $user = $this->currentUser();

        // Antrean persetujuan akhir & laporan revisi dari Backend
        $finalQueue = BackendApiService::fetch('admin/final-reviews', ['per_page' => 1]);
        $revisions = $this->revisionReports();
        $users = $this->allPages('admin/users');
        $positions = BackendApiService::fetch('admin/positions');

        $attention = [
            'final' => (int) ($finalQueue['total'] ?? 0),
            'revisi' => count($revisions),
        ];

        // Ringkasan Divisi: karyawan aktif per jabatan & berapa yang sudah melapor hari ini
        $staff = collect($users)->filter(fn ($u) => $u['is_active'] && strtolower($u['role']['name'] ?? '') !== 'admin');
        $today = now()->toDateString();
        $reportedToday = collect($this->allPages('admin/kpi-submissions', ['date_from' => $today]))
            ->filter(fn ($s) => ($s['report_date'] ?? null) === $today && in_array($s['status'], ['SUBMITTED', 'APPROVED', 'REJECTED'], true))
            ->pluck('user.id')->unique();

        $divisions = collect($positions['data'] ?? $positions ?? [])
            ->map(function ($p) use ($staff, $reportedToday) {
                $members = $staff->filter(fn ($u) => ($u['position']['id'] ?? null) === $p['id']);

                return [
                    'name' => $this->divisionMeta($p['name'])[0],
                    'staff_count' => $members->count(),
                    'done' => $members->pluck('id')->intersect($reportedToday)->count(),
                    'total' => $members->count(),
                ];
            })
            ->filter(fn ($d) => $d['staff_count'] > 0)
            ->values()
            ->all();

        $menuBadges = [
            'revisi' => $attention['revisi'],
            'staff' => $staff->count(),
            'divisi' => count($divisions),
            'final' => $attention['final'],
        ];

        return view('admin.dashboard', compact('user', 'attention', 'divisions', 'menuBadges'));
    }


    /**
     * Rekap Bulanan (Figma: "Rekap Bulanan · Per Karyawan" & "Rekap Bulanan · Per Bagian").
     * Query: mode=karyawan|bagian, bulan=Y-m, karyawan={id}, bagian={key}, tanggal=Y-m-d
     */
    public function rekapBulanan(Request $request)
    {
        $user = $this->currentUser();
        $mode = $request->query('mode') === 'bagian' ? 'bagian' : 'karyawan';
        $month = RekapBulananService::resolveMonth($request->query('bulan'));
        $months = RekapBulananService::monthChips($month);
        $bagian = RekapBulananService::resolveBagian($request->query('bagian'));
        $bagianOptions = RekapBulananService::bagianOptions();


        // Backend belum menyediakan endpoint rekap bulanan: halaman menampilkan "Data belum tersedia".
        return view('admin.rekap', compact('user', 'mode', 'month', 'months', 'bagian', 'bagianOptions'));
    }

    /**
     * Rincian Harian per bagian (Figma: "Rincian Harian · Per Bagian").
     */
    public function rincianHarian(Request $request)
    {
        $user = $this->currentUser();
        $bagian = RekapBulananService::resolveBagian($request->query('bagian'));
        $date = RekapBulananService::resolveDate($request->query('tanggal'), RekapBulananService::defaultDate(RekapBulananService::today()));
        $prevDate = RekapBulananService::stepDate($date, -1);
        $nextDate = RekapBulananService::stepDate($date, 1);

        // Backend belum menyediakan endpoint rincian harian: halaman menampilkan "Data belum tersedia".
        return view('admin.rincian_harian', compact('user', 'bagian', 'date', 'prevDate', 'nextDate'));
    }

    /**
     * Tampilkan halaman Profil Admin
     */
    public function profile()
    {
        $sessionUser = $this->currentUser();

        $user = [
            'name' => $sessionUser['name'] ?? 'User',
            'role' => 'ADMIN',
            'status' => 'Akun Aktif',
            'email' => $sessionUser['email'] ?? 'User@capaian.id',
        ];

        return view('admin.profile', compact('user'));
    }

    /**
     * Ubah email akun sendiri (Figma: Halaman Profil > UBAH EMAIL).
     * Login lewat Backend: PUT /api/admin/users/{id}. Akun demo: hanya mengubah email di sesi.
     */
    public function ubahEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Email baru wajib diisi.',
            'email.email' => 'Email tidak sesuai. Periksa kembali email yang Anda masukkan.',
        ]);

        $sessionUser = session('user', []);
        $email = strtolower(trim($data['email']));

        if (strcasecmp($email, (string) ($sessionUser['email'] ?? '')) === 0) {
            return back()->withInput()->withErrors(['email' => 'Email baru sama dengan email saat ini.']);
        }

        if (session('api_token') && ! empty($sessionUser['id'])) {
            $res = BackendApiService::send('PUT', "admin/users/{$sessionUser['id']}", [
                'name' => $sessionUser['name'] ?? 'Admin',
                'email' => $email,
            ]);
            if (! $res['ok']) {
                return back()->withInput()->withErrors(['email' => $res['first_error'] ?? $res['message']]);
            }
        }

        session(['user' => array_merge($sessionUser, ['email' => $email])]);

        return redirect()->route('admin.profile')->with('status', 'Email berhasil diperbarui.');
    }

    /**
     * Ambil semua halaman sebuah endpoint daftar Backend (maks. 20 halaman x 100 baris).
     */
    private function allPages(string $endpoint, array $query = []): array
    {
        $rows = [];
        for ($page = 1; $page <= 20; $page++) {
            $json = BackendApiService::fetch($endpoint, $query + ['per_page' => 100, 'page' => $page]);
            $rows = array_merge($rows, $json['data'] ?? []);
            $last = $json['meta']['last_page'] ?? $json['last_page'] ?? 1;
            if (! $json || $page >= $last) {
                break;
            }
        }

        return $rows;
    }

    /**
     * Laporan berstatus Ditolak (perlu revisi) dari Backend, 1 kartu = 1 karyawan + 1 tanggal.
     * Laporan yang sudah ditandai "ditindaklanjuti" oleh Admin disembunyikan.
     */
    private function revisionReports(): array
    {
        $done = RevisiTindakLanjutService::semua();

        return collect($this->allPages('admin/kpi-submissions', ['status' => 'REJECTED']))
            ->groupBy(fn ($s) => $s['user']['id'] . '_' . $s['report_date'])
            ->map(function ($items, $key) {
                $first = $items->first();
                $review = $items->pluck('review')->filter()->sortByDesc('reviewed_at')->first() ?? [];
                $reviewedAt = ! empty($review['reviewed_at']) ? \Carbon\Carbon::parse($review['reviewed_at'])->setTimezone('Asia/Jakarta') : null;
                $days = $reviewedAt ? (int) $reviewedAt->diffInDays(now()) : 0;
                $isManagerReport = strtolower($first['user']['role'] ?? '') === 'manager';

                return [
                    'key' => $key . '_' . ($review['reviewed_at'] ?? ''),
                    'name' => $first['user']['name'],
                    'division' => $this->divisionMeta($first['user']['position'] ?? null)[0],
                    'category' => $isManagerReport ? 'store_manager' : (strtolower($review['reviewer_role'] ?? '') === 'admin' ? 'owner' : 'sm'),
                    'origin' => strtolower($review['reviewer_role'] ?? '') === 'admin' ? 'Diminta Owner' : 'Diminta SM',
                    'date_label' => \Carbon\Carbon::parse($first['report_date'])->locale('id')->translatedFormat('j M Y'),
                    'days' => $days,
                    'overdue' => $days >= 2,
                    'notes' => $review['notes'] ?? '-',
                    'reviewer' => $review['reviewer'] ?? '-',
                    'reviewed_label' => $reviewedAt ? $reviewedAt->locale('id')->translatedFormat('j M, H:i') . ' WIB' : '',
                    'indicators' => $items->map(fn ($s) => [
                        'name' => $s['indicator']['name'] ?? '-',
                        'ok' => $s['answer_yes_no'] === true,
                        'evidences' => (int) ($s['evidences_count'] ?? 0),
                    ])->values()->all(),
                ];
            })
            ->reject(fn ($r) => in_array($r['key'], $done, true))
            ->sortByDesc('days')
            ->values()
            ->all();
    }

    public function revisi()
    {
        $user = $this->currentUser();
        $reports = $this->revisionReports();

        $filterCounts = [
            'all' => count($reports),
            'sm' => collect($reports)->where('category', 'sm')->count(),
            'owner' => collect($reports)->where('category', 'owner')->count(),
            'store_manager' => collect($reports)->where('category', 'store_manager')->count(),
        ];

        return view('admin.revisi', compact('user', 'reports', 'filterCounts'));
    }

    /**
     * Tandai laporan revisi sudah ditegur/ditindaklanjuti manual (disimpan di FE; Backend belum punya fiturnya).
     */
    public function tindakLanjutRevisi(Request $request)
    {
        $key = (string) $request->validate(['key' => ['required', 'string', 'max:120']])['key'];
        RevisiTindakLanjutService::tandai($key);

        return redirect()->route('admin.revisi')->with('status', 'Laporan ditandai sudah ditindaklanjuti.');
    }


    /**
     * Pemetaan nama jabatan Backend -> label, kunci filter, dan kelas warna di UI.
     */
    private function divisionMeta(?string $position): array
    {
        return match (strtolower((string) $position)) {
            'store manager' => ['Store Manager', 'store_manager', 'store-manager'],
            'frontliner' => ['Frontliner', 'frontliner', 'frontliner'],
            'produksi' => ['Produksi', 'produksi', 'produksi'],
            'driver' => ['Driver', 'driver', 'driver'],
            'sales' => ['Sales & Marketing', 'sales', 'frontliner'],
            'spv' => ['Supervisor', 'supervisor', 'store-manager'],
            '' => ['Admin', 'admin', 'outline'],
            default => [(string) $position, \Illuminate\Support\Str::slug((string) $position, '_'), 'outline'],
        };
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim(preg_replace('/\(.*?\)/', '', $name)));
        return strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
    }

    /**
     * Tampilkan halaman Kelola Akun.
     * Data dari Backend: GET /api/admin/users & /api/admin/positions.
     */
    public function kelolaAkun()
    {
        $user = $this->currentUser();

        $usersJson = BackendApiService::fetch('admin/users', ['per_page' => 1]);
        $positionsJson = BackendApiService::fetch('admin/positions');
        $isLive = $usersJson !== null && $positionsJson !== null;

        $positions = array_map(fn ($p) => [
            'id' => $p['id'],
            'label' => $this->divisionMeta($p['name'])[0],
        ], $positionsJson['data'] ?? $positionsJson ?? []);

        $raw = array_map(fn ($u) => [
            'id' => $u['id'],
            'name' => $u['name'],
            'email' => $u['email'],
            'active' => (bool) $u['is_active'],
            'position' => $u['position']['name'] ?? null,
            'position_id' => $u['position']['id'] ?? null,
            'is_admin' => strtolower($u['role']['name'] ?? '') === 'admin',
            'created_at' => $u['created_at'] ?? null,
        ], $this->allPages('admin/users'));

        $accounts = array_map(function ($u) {
            [$label, $key, $pill] = $this->divisionMeta($u['position']);
            return array_merge($u, [
                'can_access' => AksesAkunService::bisaAkses($u['email']),
                'division' => $label,
                'category' => $key,
                'pill_class' => $pill,
                'initials' => $this->initials($u['name']),
                'joined' => $u['created_at']
                    ? 'Bergabung ' . \Carbon\Carbon::parse($u['created_at'])->locale('id')->translatedFormat('M Y')
                    : 'Tanggal bergabung tidak tersedia',
            ]);
        }, $raw);

        // Hitung jumlah per divisi untuk chip filter
        $divisions = [];
        foreach ($accounts as $acc) {
            $divisions[$acc['category']] ??= ['key' => $acc['category'], 'label' => $acc['division'], 'count' => 0];
            $divisions[$acc['category']]['count']++;
        }

        $nonAdmin = array_filter($accounts, fn ($a) => ! $a['is_admin']);
        $summary = [
            'total_karyawan' => count($nonAdmin),
            'total_divisi' => count(array_unique(array_column($nonAdmin, 'category'))),
        ];

        $filterCounts = [
            'all' => count($accounts),
            'divisions' => array_values($divisions),
        ];

        return view('admin.accounts', compact('user', 'summary', 'filterCounts', 'accounts', 'positions', 'isLive'));
    }

    /**
     * Role Backend ditentukan dari jabatan: Store Manager -> Manager, selain itu -> Karyawan.
     */
    private function roleIdForPosition(int $positionId): ?int
    {
        $positions = BackendApiService::fetch('admin/positions');
        $roles = BackendApiService::fetch('admin/roles');
        if (! $positions || ! $roles) {
            return null;
        }

        $posName = collect($positions['data'] ?? $positions)->firstWhere('id', $positionId)['name'] ?? '';
        $wanted = strtolower($posName) === 'store manager' ? 'manager' : 'karyawan';

        return collect($roles['data'] ?? $roles)->first(fn ($r) => strtolower($r['name']) === $wanted)['id'] ?? null;
    }

    private function backendResult(array $res, string $okMessage)
    {
        if ($res['ok']) {
            return redirect()->route('admin.accounts')->with('status', $okMessage);
        }

        return back()->withInput()->with('error', $res['first_error'] ?? $res['message'])
            ->withErrors($res['errors'] ?: ['backend' => $res['message']]);
    }

    /**
     * Tambah akun baru (POST /api/admin/users)
     */
    public function simpanAkun(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'position_id' => ['required', 'integer'],
        ]);

        $roleId = $this->roleIdForPosition((int) $data['position_id']);
        if (! $roleId) {
            return back()->withInput()->with('error', 'Tidak dapat terhubung ke Backend. Akun belum dibuat.');
        }

        $res = BackendApiService::send('POST', 'admin/users', $data + ['role_id' => $roleId, 'is_active' => true]);

        return $this->backendResult($res, "Akun {$data['name']} berhasil dibuat.");
    }

    /**
     * Ubah data akun (PUT /api/admin/users/{id})
     */
    public function ubahAkun(Request $request, int $id)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'position_id' => ['nullable', 'integer'],
            'old_email' => ['nullable', 'email'],
        ]);

        $oldEmail = $data['old_email'] ?? $data['email'];
        unset($data['old_email']);

        if (BackendApiService::connected()) {
            $payload = array_filter($data, fn ($v) => $v !== null);
            if (! empty($payload['position_id'])) {
                $payload['role_id'] = $this->roleIdForPosition((int) $payload['position_id']);
            }

            $res = BackendApiService::send('PUT', "admin/users/{$id}", $payload);
            if (! $res['ok']) {
                return $this->backendResult($res, '');
            }
        }

        // Status Akun Aktif (izin login & isi KPI harian) — disimpan di FE, lihat AksesAkunService
        AksesAkunService::gantiEmail($oldEmail, $data['email']);
        AksesAkunService::atur($data['email'], $request->boolean('can_access'));

        return redirect()->route('admin.accounts')->with('status', "Data {$data['name']} berhasil diperbarui.");
    }

    /**
     * Aktif / nonaktifkan akun (PATCH /api/admin/users/{id}/status)
     */
    public function ubahStatusAkun(Request $request, int $id)
    {
        $active = $request->boolean('is_active');
        $res = BackendApiService::send('PATCH', "admin/users/{$id}/status", ['is_active' => $active]);

        return $this->backendResult($res, $active ? 'Akun berhasil diaktifkan kembali.' : 'Akun berhasil dinonaktifkan.');
    }

    /**
     * Kelola KPI: indikator aktif per jabatan dari Backend (GET /api/positions/{id}/kpis).
     */
    public function kelolaKpi()
    {
        $user = $this->currentUser();
        [$divisions, $kpiGroups] = $this->liveKpiGroups() ?? [[], []];
        $activeDivision = $divisions[0] ?? null;

        return view('admin.kpi', compact('user', 'divisions', 'activeDivision', 'kpiGroups'));
    }

    /**
     * @return array{0: array<int,string>, 1: array<string,array>}|null
     */
    private function liveKpiGroups(): ?array
    {
        $positions = BackendApiService::fetch('admin/positions');
        if (! $positions) {
            return null;
        }

        $freq = ['DAILY' => 'Harian', 'WEEKLY' => 'Mingguan', 'MONTHLY' => 'Bulanan'];
        $fmt = fn ($n) => str_ends_with($s = number_format((float) $n, 2, ',', '.'), ',00') ? substr($s, 0, -3) : $s;

        $divisions = [];
        $kpiGroups = [];
        foreach ($positions['data'] ?? [] as $pos) {
            $label = $this->divisionMeta($pos['name'])[0];
            $items = BackendApiService::fetch("positions/{$pos['id']}/kpis")['data'] ?? null;
            if ($items === null) {
                continue;
            }

            $groups = [];
            foreach ($items as $pk) {
                $ind = $pk['indicator'] ?? [];
                $cat = $ind['category']['name'] ?? 'Lainnya';
                $isYesNo = ($pk['input_type'] ?? '') === 'YES_NO';
                $unit = $ind['unit'] ?? '';

                $groups[$cat] ??= [
                    'id' => \Illuminate\Support\Str::slug($cat),
                    'title' => $cat,
                    'count_text' => null,
                    'expanded' => true,
                    'items' => [],
                ];
                $groups[$cat]['items'][] = [
                    'id' => $pk['id'],
                    'name' => $ind['name'] ?? '-',
                    'subtitle' => ($isYesNo ? 'Target: Ya' : (($pk['calculation_type'] ?? '') === 'LOWER_IS_BETTER' ? 'Maksimal ' : 'Target: ') . $fmt($pk['target']) . ' ' . $unit)
                        . ' · Bobot ' . $fmt($pk['weight']) . '% · ' . ($freq[$pk['frequency']] ?? $pk['frequency']),
                    'badge' => $isYesNo ? 'Ceklis' : 'Angka · ' . $unit,
                    'type' => $isYesNo ? 'checklist' : 'number',
                ];
            }

            foreach ($groups as &$g) {
                $g['count_text'] = count($g['items']) . ' indikator';
            }
            unset($g);

            $divisions[] = $label;
            $kpiGroups[$label] = array_values($groups);
        }

        return $divisions ? [$divisions, $kpiGroups] : null;
    }

    /**
     * Antrean persetujuan akhir dari Backend (GET /api/admin/final-reviews + detail per laporan).
     * null = tidak terhubung ke Backend.
     */
    private function liveFinalReviews(): ?array
    {
        $queue = BackendApiService::fetch('admin/final-reviews', ['per_page' => 50]);
        if ($queue === null) {
            return null;
        }

        $reports = [];
        foreach ($queue['data'] ?? [] as $row) {
            $userId = $row['user']['id'];
            $date = $row['report_date'];
            $items = BackendApiService::fetch("admin/final-reviews/{$userId}/{$date}")['data'] ?? [];

            $checklists = array_map(function ($s) {
                $ind = $s['indicator'] ?? [];
                $isYesNo = ($ind['input_type'] ?? '') === 'YES_NO';
                $value = $isYesNo
                    ? ($s['answer_yes_no'] ? 'Ya' : 'Tidak')
                    : rtrim(rtrim(number_format((float) ($s['numeric_value'] ?? 0), 2, ',', '.'), '0'), ',') . ' ' . ($ind['unit'] ?? '');
                $done = $isYesNo
                    ? (bool) $s['answer_yes_no']
                    : (($ind['calculation_type'] ?? '') === 'LOWER_IS_BETTER'
                        ? (float) $s['numeric_value'] <= (float) ($ind['target'] ?? 0)
                        : (float) $s['numeric_value'] >= (float) ($ind['target'] ?? 0));

                return [
                    'title' => $ind['name'] ?? '-',
                    'target_sub' => $isYesNo ? 'Jawaban: ' . $value : 'Target: ' . ($ind['target'] ?? 0) . ' · Hasil: ' . trim($value),
                    'is_checked' => $done,
                ];
            }, $items);

            $evidences = collect($items)->flatMap(fn ($s) => $s['evidences'] ?? [])->values()->all();
            $review = collect($items)->pluck('review')->filter()->first() ?? [];
            [$division] = $this->divisionMeta($row['user']['position'] ?? null);

            $reports[] = [
                'user_id' => $userId,
                'date' => $date,
                'name' => $row['user']['name'],
                'division' => $division,
                // Tanpa review Manager = laporan Store Manager sendiri, diputuskan langsung oleh Admin
                'is_manager_report' => empty($review),
                'approval_meta' => empty($review)
                    ? 'Laporan Store Manager · menunggu keputusan Admin'
                    : 'Disetujui oleh ' . ($review['reviewer'] ?? 'Store Manager')
                        . (! empty($review['reviewed_at']) ? ' · ' . \Carbon\Carbon::parse($review['reviewed_at'])->locale('id')->translatedFormat('j M, H:i') : ''),
                'report_label' => \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('l, j F Y'),
                'evidences' => $evidences,
                'checklists' => $checklists,
                'checklist_summary' => collect($checklists)->where('is_checked', true)->count() . '/' . count($checklists) . ' Terpenuhi',
                'sm_note' => $review['notes'] ?? null,
            ];
        }

        return $reports;
    }

    /**
     * Putuskan laporan: Approve Final / Tolak (POST /api/admin/final-reviews/{user}/{date})
     */
    public function putuskanLaporan(Request $request, int $userId, string $date)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:APPROVED,REJECTED'],
            'notes' => ['nullable', 'string', 'max:255', 'required_if:decision,REJECTED'],
        ], [
            'notes.required_if' => 'Tulis alasan penolakan di kolom catatan sebelum menolak laporan.',
        ]);

        $res = BackendApiService::send('POST', "admin/final-reviews/{$userId}/{$date}", array_filter($data, fn ($v) => $v !== null));

        return redirect()->route('admin.approve')->with(
            $res['ok'] ? 'status' : 'error',
            $res['ok'] ? ($res['message'] ?: 'Laporan berhasil diproses.') : ($res['first_error'] ?? $res['message'])
        );
    }

    /**
     * Approve Laporan: antrean persetujuan akhir dari Backend. null = Backend tidak dapat dihubungi.
     */
    public function approveLaporan()
    {
        $user = $this->currentUser();
        $liveReports = $this->liveFinalReviews();

        return view('admin.approve', compact('user', 'liveReports'));
    }
}
