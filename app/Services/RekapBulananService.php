<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Rekap Bulanan & Rincian Harian Admin (Figma: "Rekap Bulanan · Per Karyawan", "Rekap Bulanan · Per Bagian",
 * "Rincian Harian · Per Bagian"), dihitung dari data asli Backend:
 *  - karyawan: GET /api/admin/users
 *  - isian: GET /api/admin/kpi-submissions (filter user_id / position_id / rentang periode)
 *
 * Aturan hitung (Figma):
 *  - Satu hari laporan = semua isian karyawan dengan tanggal laporan yang sama. Draf tidak dihitung sebagai isian.
 *  - Status hari: ada yang Ditolak -> Ditolak; ada yang Menunggu -> Menunggu; semua Disetujui -> Disetujui.
 *  - Hanya hari Disetujui yang ikut dihitung di total & rata-rata. Menunggu dan Ditolak belum masuk total.
 *  - Susulan = isian dikirim setelah tanggal laporannya lewat.
 *  - Hari Minggu bukan hari kerja. Hari kerja tanpa isian tampil sebagai Kosong, bukan 0.
 */
class RekapBulananService
{
    private const TZ = 'Asia/Jakarta';

    private const BAGIAN = [
        'store_manager' => 'Store Manager',
        'frontliner' => 'Frontliner',
        'produksi' => 'Produksi',
        'driver' => 'Driver',
        'sales' => 'Sales & Marketing',
        'supervisor' => 'Supervisor',
    ];

    /** Nama jabatan Backend (huruf kecil) -> kunci bagian */
    private const POSITION_KEY = [
        'store manager' => 'store_manager',
        'frontliner' => 'frontliner',
        'produksi' => 'produksi',
        'driver' => 'driver',
        'sales' => 'sales',
        'spv' => 'supervisor',
        'supervisor' => 'supervisor',
    ];

    // ------------------------------------------------------------------ navigasi

    public static function bagianOptions(): array
    {
        $out = [];
        foreach (self::BAGIAN as $key => $label) {
            $out[] = ['key' => $key, 'label' => $label];
        }

        return $out;
    }

    public static function today(): Carbon
    {
        return Carbon::now(self::TZ)->startOfDay();
    }

    /** Bulan dari query (Y-m) atau bulan berjalan; tidak boleh melewati bulan berjalan. */
    public static function resolveMonth(?string $ym): Carbon
    {
        $now = self::today()->startOfMonth();
        if ($ym && preg_match('/^\d{4}-\d{2}$/', $ym)) {
            $m = Carbon::createFromFormat('!Y-m', $ym, self::TZ);
            if ($m && $m->lte($now)) {
                return $m;
            }
        }

        return $now;
    }

    /** 3 bulan terakhir untuk chip "Pilih bulan". */
    public static function monthChips(Carbon $selected): array
    {
        $now = self::today()->startOfMonth();
        $chips = [];
        for ($i = 0; $i < 3; $i++) {
            $m = $now->copy()->subMonths($i);
            $chips[] = [
                'value' => $m->format('Y-m'),
                'label' => $m->locale('id')->translatedFormat('M Y'),
                'active' => $m->format('Y-m') === $selected->format('Y-m'),
            ];
        }

        return $chips;
    }

    public static function resolveBagian(?string $key): string
    {
        return isset(self::BAGIAN[$key]) ? $key : 'frontliner';
    }

    public static function bagianLabel(string $key): string
    {
        return self::BAGIAN[$key] ?? $key;
    }

    /** Hari kerja terakhir (≤ hari ini) di bulan terpilih — tanggal awal Rincian Harian. */
    public static function defaultDate(Carbon $month): Carbon
    {
        $d = $month->isSameMonth(self::today()) ? self::today() : $month->copy()->endOfMonth()->startOfDay();
        while ($d->isSunday()) {
            $d->subDay();
        }

        return $d;
    }

    public static function resolveDate(?string $ymd, Carbon $fallback): Carbon
    {
        if ($ymd && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            $d = Carbon::createFromFormat('!Y-m-d', $ymd, self::TZ);
            if ($d && $d->lte(self::today())) {
                return $d;
            }
        }

        return $fallback;
    }

    /** Hari kerja sebelum/sesudah (lewati Minggu). Sesudah: null bila melewati hari ini. */
    public static function stepDate(Carbon $d, int $dir): ?Carbon
    {
        $n = $d->copy()->addDays($dir);
        while ($n->isSunday()) {
            $n->addDays($dir);
        }

        return $n->gt(self::today()) ? null : $n;
    }

    // ------------------------------------------------------------------ data Backend

    private static function allPages(string $endpoint, array $query = []): ?array
    {
        $rows = [];
        for ($page = 1; $page <= 50; $page++) {
            $json = BackendApiService::fetch($endpoint, $query + ['per_page' => 100, 'page' => $page]);
            if ($json === null) {
                return $page === 1 ? null : $rows;
            }
            $rows = array_merge($rows, $json['data'] ?? []);
            if ($page >= ($json['meta']['last_page'] ?? $json['last_page'] ?? 1)) {
                break;
            }
        }

        return $rows;
    }

    /** @return array<int, array>|null  semua karyawan (bukan Admin); null = Backend tidak dapat dihubungi */
    public static function employees(?string $bagian = null): ?array
    {
        static $cache = null;
        $cache ??= self::allPages('admin/users');
        if ($cache === null) {
            return null;
        }

        $list = [];
        foreach ($cache as $u) {
            if (strtolower($u['role']['name'] ?? '') === 'admin') {
                continue;
            }
            $key = self::POSITION_KEY[strtolower($u['position']['name'] ?? '')] ?? null;
            if (! $key || ($bagian && $key !== $bagian)) {
                continue;
            }
            $parts = preg_split('/\s+/', trim(preg_replace('/\(.*?\)/', '', $u['name'])));
            $list[] = [
                'id' => $u['id'],
                'name' => $u['name'],
                'initials' => strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1)),
                'bagian' => $key,
                'bagian_label' => self::bagianLabel($key),
                'position_id' => $u['position']['id'] ?? null,
                'code' => str_pad((string) $u['id'], 3, '0', STR_PAD_LEFT),
                'inactive' => ! ($u['is_active'] ?? true),
                'joined' => ! empty($u['created_at']) ? Carbon::parse($u['created_at'])->setTimezone(self::TZ)->startOfDay() : null,
            ];
        }
        usort($list, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $list;
    }

    /** Isian terkirim (bukan draf) dalam rentang tanggal laporan. */
    private static function submissions(array $filter, Carbon $from, Carbon $to): Collection
    {
        // Filter Backend memakai periode isian; ambil sedikit lebih lebar lalu saring per tanggal laporan
        $rows = self::allPages('admin/kpi-submissions', $filter + [
            'date_from' => $from->copy()->subDays(31)->toDateString(),
            'date_to' => $to->copy()->addDays(31)->toDateString(),
        ]) ?? [];

        return collect($rows)
            ->filter(fn ($s) => ($s['status'] ?? '') !== 'DRAFT')
            ->filter(fn ($s) => ($d = substr($s['report_date'] ?? '', 0, 10)) >= $from->toDateString() && $d <= $to->toDateString())
            ->values();
    }

    // ------------------------------------------------------------------ perhitungan

    /** Status satu hari dari isian-isian di tanggal itu. */
    private static function dayEntry(Collection $rows): array
    {
        $statuses = $rows->pluck('status');
        $status = match (true) {
            $statuses->contains('REJECTED') => 'ditolak',
            $statuses->contains('SUBMITTED') => 'menunggu',
            default => 'disetujui',
        };

        $reportDate = substr($rows->first()['report_date'], 0, 10);
        $firstSent = $rows->pluck('submitted_at')->filter()->sort()->first();
        $susulan = $firstSent && Carbon::parse($firstSent)->setTimezone(self::TZ)->toDateString() > $reportDate;
        $hasPhoto = $rows->sum(fn ($s) => (int) ($s['evidences_count'] ?? 0)) > 0;

        return [
            'status' => $status,
            'susulan' => $susulan,
            'foto' => $hasPhoto ? ['disetujui' => 'sah', 'menunggu' => 'belum', 'ditolak' => 'tidak_sah'][$status] : null,
            'counted' => $status === 'disetujui',
        ];
    }

    private static function num($v, int $dec = 0): string
    {
        $s = number_format((float) $v, $dec, ',', '.');

        return $dec > 0 ? rtrim(rtrim($s, '0'), ',') : $s;
    }

    private static function formatValue(array $s): string
    {
        $ind = $s['indicator'] ?? [];
        if (($ind['input_type'] ?? 'YES_NO') === 'YES_NO') {
            return $s['answer_yes_no'] ? 'Ya' : 'Tidak';
        }

        return trim(self::num($s['numeric_value'] ?? 0, 2) . ' ' . ($ind['unit'] ?? ''));
    }

    private static function tercapai(array $s): bool
    {
        $ind = $s['indicator'] ?? [];
        if (($ind['input_type'] ?? 'YES_NO') === 'YES_NO') {
            return false; // penanda Tercapai hanya untuk isian angka (Figma)
        }
        $v = (float) ($s['numeric_value'] ?? 0);
        $t = (float) ($ind['target'] ?? 0);

        return ($ind['calculation_type'] ?? '') === 'LOWER_IS_BETTER' ? $v <= $t : $v >= $t;
    }

    /** Rekap satu karyawan dalam satu bulan (Figma: Rekap Bulanan · Per Karyawan). */
    public static function perKaryawan(array $emp, Carbon $month, ?Collection $rows = null): array
    {
        $today = self::today();
        $rows ??= self::submissions(['user_id' => $emp['id']], $month->copy()->startOfMonth(), $month->copy()->endOfMonth());
        $byDay = $rows->groupBy(fn ($s) => substr($s['report_date'], 0, 10));

        $working = $filled = $susulan = $empty = 0;
        $calendar = $entries = [];
        $end = $month->copy()->endOfMonth()->startOfDay();

        for ($d = $month->copy()->startOfMonth(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $beforeJoin = $emp['joined'] && $d->lt($emp['joined']);
            $entry = null;

            if ($byDay->has($key)) {
                $entry = self::dayEntry($byDay[$key]);
            } elseif (! $d->isSunday() && $d->lte($today) && ! $beforeJoin) {
                $entry = ['status' => 'kosong', 'susulan' => false, 'foto' => null, 'counted' => false];
            }

            if ($entry) {
                $working++;
                $entry['status'] === 'kosong' ? $empty++ : $filled++;
                $susulan += $entry['susulan'] ? 1 : 0;
                $entries[] = ['date' => $d->copy(), 'entry' => $entry];
            }

            $calendar[] = [
                'day' => $d->day,
                'state' => $entry['status'] ?? ($d->isSunday() ? 'libur' : 'nanti'),
                'susulan' => $entry['susulan'] ?? false,
                'label' => $d->locale('id')->translatedFormat('l, j F Y'),
            ];
        }

        // Hal yang dicatat: dari isian di bulan ini; total & rata-rata hanya dari hari Disetujui
        $countedDays = collect($entries)->filter(fn ($x) => $x['entry']['counted'])->map(fn ($x) => $x['date']->toDateString());
        $counted = $rows->filter(fn ($s) => $countedDays->contains(substr($s['report_date'], 0, 10)));

        $metrics = $rows->groupBy(fn ($s) => $s['indicator']['name'] ?? '-')->map(function ($all, $name) use ($counted) {
            $ind = $all->first()['indicator'] ?? [];
            $c = $counted->filter(fn ($s) => ($s['indicator']['name'] ?? '-') === $name);
            $n = $c->count();
            $unit = $ind['unit'] ?? '';

            if (($ind['input_type'] ?? 'YES_NO') === 'YES_NO') {
                $ya = $c->filter(fn ($s) => $s['answer_yes_no'])->count();

                return ['name' => $name, 'unit' => 'Ya/Tidak', 'inactive' => false, 'total' => "{$ya} Ya", 'avg' => '-',
                    'note' => "{$ya} Ya dan " . ($n - $ya) . " Tidak dari {$n} hari dihitung", 'total_raw' => $ya, 'avg_raw' => 0];
            }

            $total = $c->sum(fn ($s) => (float) $s['numeric_value']);

            return ['name' => $name, 'unit' => $unit ?: 'Angka', 'inactive' => false,
                'total' => trim(self::num($total, 2) . ' ' . $unit), 'avg' => $n ? self::num($total / $n, 1) : '-',
                'note' => null, 'total_raw' => $total, 'avg_raw' => $n ? $total / $n : 0];
        })->values()->all();

        $details = array_map(fn ($x) => [
            'day' => $x['date']->day,
            'dow' => $x['date']->locale('id')->translatedFormat('D'),
            'status' => $x['entry']['status'],
            'susulan' => $x['entry']['susulan'],
            'foto' => $x['entry']['foto'],
        ], array_reverse($entries));

        $last = $month->isSameMonth($today) ? $today : $month->copy()->endOfMonth();

        return [
            'period' => '1 sampai ' . $last->locale('id')->translatedFormat('j F Y') . " · {$working} hari kerja",
            'month_label' => $month->locale('id')->translatedFormat('F Y'),
            'filled' => $filled,
            'susulan' => $susulan,
            'empty' => $empty,
            'counted' => $countedDays->count(),
            'metrics' => $metrics,
            'calendar' => $calendar,
            'offset' => $month->copy()->startOfMonth()->dayOfWeekIso - 1, // Senin = 0
            'details' => $details,
        ];
    }

    /** Rekap satu bagian dalam satu bulan (Figma: Rekap Bulanan · Per Bagian). */
    public static function perBagian(string $bagian, Carbon $month): array
    {
        $emps = self::employees($bagian) ?? [];
        $rows = $emps && $emps[0]['position_id']
            ? self::submissions(['position_id' => $emps[0]['position_id']], $month->copy()->startOfMonth(), $month->copy()->endOfMonth())
            : collect();
        $byUser = $rows->groupBy(fn ($s) => $s['user']['id']);

        // Kolom: 3 hal yang paling sering dicatat, angka didahulukan
        $columns = $rows->groupBy(fn ($s) => $s['indicator']['name'] ?? '-')
            ->sortBy(fn ($g) => [($g->first()['indicator']['input_type'] ?? '') === 'NUMERIC' ? 0 : 1, -$g->count()])
            ->keys()->take(3)->values();

        $out = [];
        $filled = $susulan = $empty = $working = 0;
        foreach ($emps as $emp) {
            $r = self::perKaryawan($emp, $month, $byUser[$emp['id']] ?? collect());
            $working = max($working, $r['filled'] + $r['empty']);
            $filled += $r['filled'];
            $susulan += $r['susulan'];
            $empty += $r['empty'];

            $out[] = [
                'emp' => $emp + ['inactive_since' => $emp['inactive']],
                'cells' => $columns->map(function ($name) use ($r) {
                    $m = collect($r['metrics'])->firstWhere('name', $name);

                    return $m
                        ? ['total' => $m['total'], 'avg' => $m['avg'] !== '-' ? 'rata ' . $m['avg'] : '']
                        : ['total' => '-', 'avg' => ''];
                })->all(),
                'filled' => $r['filled'],
                'susulan' => $r['susulan'] ? $r['susulan'] . ' susulan' : '-',
            ];
        }

        return [
            'label' => self::bagianLabel($bagian),
            'count' => count($emps),
            'inactive' => count(array_filter($emps, fn ($e) => $e['inactive'])),
            'period' => $month->locale('id')->translatedFormat('F Y') . ' · ' . count($emps) . " karyawan × {$working} hari kerja",
            'filled' => $filled,
            'susulan' => $susulan,
            'empty' => $empty,
            'columns' => $columns->all(),
            'rows' => $out,
        ];
    }

    /** Isian semua karyawan satu bagian pada satu tanggal (Figma: Rincian Harian · Per Bagian). */
    public static function rincianHarian(string $bagian, Carbon $date): array
    {
        $emps = self::employees($bagian) ?? [];
        $rows = $emps && $emps[0]['position_id']
            ? self::submissions(['position_id' => $emps[0]['position_id']], $date, $date)
            : collect();
        $byUser = $rows->groupBy(fn ($s) => $s['user']['id']);

        $cards = [];
        $mengisi = $susulan = $kosong = 0;
        foreach ($emps as $emp) {
            $mine = $byUser[$emp['id']] ?? collect();
            $emp['inactive_since'] = $emp['inactive'];

            if ($mine->isEmpty()) {
                $kosong++;
                $cards[] = ['emp' => $emp, 'entry' => ['status' => 'kosong', 'susulan' => false, 'foto' => null], 'rows' => [],
                    'note' => 'Belum mengisi pada tanggal ini. Ditampilkan sebagai Kosong, bukan 0.', 'note_tone' => null];
                continue;
            }

            $mengisi++;
            $e = self::dayEntry($mine);
            $susulan += $e['susulan'] ? 1 : 0;
            $note = match (true) {
                $e['status'] === 'ditolak' => ['Laporan ditolak dan dikembalikan untuk revisi. Isian tidak ikut dihitung.', 'error'],
                $e['status'] === 'menunggu' => ['Masih menunggu persetujuan, belum ikut dihitung.', null],
                $e['susulan'] => ['Diisi susulan setelah tanggalnya lewat.', null],
                default => [null, null],
            };

            $cards[] = [
                'emp' => $emp,
                'entry' => $e,
                'rows' => $mine->map(fn ($s) => [
                    'name' => $s['indicator']['name'] ?? '-',
                    'value' => self::formatValue($s),
                    'tercapai' => self::tercapai($s),
                ])->values()->all(),
                'note' => $note[0],
                'note_tone' => $note[1],
            ];
        }

        return [
            'label' => self::bagianLabel($bagian),
            'date_label' => $date->locale('id')->translatedFormat('l, j F Y'),
            'chip' => $date->locale('id')->translatedFormat('j M'),
            'mengisi' => $mengisi,
            'susulan' => $susulan,
            'kosong' => $kosong,
            'cards' => $cards,
        ];
    }
}
