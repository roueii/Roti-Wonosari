<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Helper navigasi halaman Rekap Bulanan & Rincian Harian Admin (pilihan bulan, bagian, tanggal).
 *
 * Backend belum menyediakan endpoint rekap bulanan, sehingga halaman menampilkan "Data belum tersedia".
 * Aturan hitung dari Figma untuk nanti:
 *   - Hanya isian Disetujui yang fotonya tidak "Tidak sah" yang ikut dihitung.
 *   - Isian Menunggu dan Ditolak belum masuk total. Rata-rata = total / jumlah hari yang dihitung.
 *   - Hari Minggu bukan hari kerja. Karyawan yang tidak mengisi tampil sebagai Kosong, bukan 0.
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
        'supervisor' => 'SPV',
    ];

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
}
