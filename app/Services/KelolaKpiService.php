<?php

namespace App\Services;

/**
 * Kelola KPI Admin: katalog indikator (admin/kpi-indicators) dan daftar indikator per jabatan
 * (positions/{id}/kpis, disimpan lewat PUT admin/positions/{id}/kpis).
 *
 * Aturan Backend yang diikuti:
 *  - Total bobot satu jabatan harus tepat 100, jadi bobot lain disesuaikan proporsional
 *    setiap kali indikator ditambah, diubah bobotnya, atau dilepas.
 *  - Satu jabatan minimal punya 1 indikator.
 *  - Indikator yang sudah punya capaian tidak dihapus fisik (Backend menonaktifkannya).
 */
class KelolaKpiService
{
    public const FREQUENCIES = ['DAILY' => 'Harian', 'WEEKLY' => 'Mingguan', 'MONTHLY' => 'Bulanan'];

    /** @return array<int, array> daftar indikator aktif sebuah jabatan dari Backend */
    public static function positionKpis(int $positionId): ?array
    {
        return BackendApiService::fetch("positions/{$positionId}/kpis")['data'] ?? null;
    }

    /** @return array<int, array> semua indikator di katalog (semua halaman) */
    public static function catalog(): array
    {
        $rows = [];
        for ($page = 1; $page <= 20; $page++) {
            $json = BackendApiService::fetch('admin/kpi-indicators', ['per_page' => 100, 'page' => $page]);
            $rows = array_merge($rows, $json['data'] ?? []);
            if (! $json || $page >= ($json['meta']['last_page'] ?? 1)) {
                break;
            }
        }

        return $rows;
    }

    public static function categories(): array
    {
        return array_map(fn ($c) => ['id' => $c['id'], 'name' => $c['name']],
            BackendApiService::fetch('kpi-categories')['data'] ?? []);
    }

    /**
     * Susun ulang daftar indikator jabatan lalu kirim ke Backend.
     *
     * @param  array<int, array>  $current  hasil positionKpis()
     * @param  int|null  $fixedId  id indikator yang bobotnya ditetapkan Admin (bobot lain menyesuaikan)
     * @param  array|null  $fixed  ['kpi_indicator_id','target','weight','frequency'] untuk indikator itu (baru/ubah)
     * @param  int|null  $removeId  id indikator yang dilepas dari jabatan
     */
    public static function sync(int $positionId, array $current, ?array $fixed = null, ?int $removeId = null): array
    {
        $items = [];
        foreach ($current as $pk) {
            $id = (int) $pk['indicator']['id'];
            if ($id === $removeId || ($fixed && $id === (int) $fixed['kpi_indicator_id'])) {
                continue;
            }
            $items[] = [
                'kpi_indicator_id' => $id,
                'target' => (float) $pk['target'],
                'weight' => (float) $pk['weight'],
                'frequency' => $pk['frequency'],
            ];
        }

        if (! $items && ! $fixed) {
            return ['ok' => false, 'message' => 'Bagian ini minimal harus punya 1 hal yang dicatat. Tambahkan hal lain dulu sebelum melepas yang ini.', 'first_error' => null];
        }

        $items = self::balance($items, $fixed);

        return BackendApiService::send('PUT', "admin/positions/{$positionId}/kpis", ['items' => $items]);
    }

    /**
     * Bobot total = 100: indikator $fixed memakai bobotnya sendiri, sisanya dibagi proporsional ke yang lain.
     */
    private static function balance(array $others, ?array $fixed): array
    {
        if (! $others) {
            return [array_merge($fixed, ['weight' => 100.0])];
        }

        $fixedWeight = $fixed ? min(max((float) $fixed['weight'], 0.01), 99.99) : 0.0;
        $remaining = 100 - $fixedWeight;
        $sum = array_sum(array_column($others, 'weight')) ?: count($others);

        foreach ($others as $i => $o) {
            $base = array_sum(array_column($others, 'weight')) ? $o['weight'] : 1;
            $others[$i]['weight'] = max(round($base / $sum * $remaining, 2), 0.01);
        }

        $all = $fixed ? array_merge($others, [array_merge($fixed, ['weight' => round($fixedWeight, 2)])]) : $others;

        // Pembulatan: selisih ditambahkan ke indikator berbobot terbesar agar total tepat 100
        $diff = round(100 - array_sum(array_column($all, 'weight')), 2);
        if ($diff != 0) {
            $max = array_keys(array_column($all, 'weight'), max(array_column($all, 'weight')))[0];
            $all[$max]['weight'] = round($all[$max]['weight'] + $diff, 2);
        }

        return array_values($all);
    }

    /** Bobot awal yang disarankan untuk indikator baru: rata-rata bila semua bobot sama besar. */
    public static function suggestedWeight(int $count): float
    {
        return round(100 / ($count + 1), 2);
    }
}
