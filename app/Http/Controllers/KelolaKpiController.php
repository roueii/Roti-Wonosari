<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use App\Services\KelolaKpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Halaman Kelola KPI Admin, tersambung ke Backend:
 *  - katalog indikator: GET/POST/PUT/DELETE admin/kpi-indicators
 *  - indikator per jabatan (bagian): GET positions/{id}/kpis, PUT admin/positions/{id}/kpis
 *  - kelompok: GET kpi-categories
 */
class KelolaKpiController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->currentUser();
        $positions = BackendApiService::fetch('admin/positions')['data'] ?? null;
        $unavailable = $positions === null;

        $catalog = $unavailable ? [] : KelolaKpiService::catalog();
        $categories = $unavailable ? [] : KelolaKpiService::categories();
        $fmt = fn ($n) => str_ends_with($s = number_format((float) $n, 2, ',', '.'), ',00') ? substr($s, 0, -3) : $s;

        $panels = [];
        foreach ($positions ?? [] as $pos) {
            $kpis = KelolaKpiService::positionKpis($pos['id']) ?? [];
            $assigned = array_map(fn ($pk) => (int) $pk['indicator']['id'], $kpis);

            $groups = [];
            foreach ($kpis as $pk) {
                $ind = $pk['indicator'];
                $isYesNo = ($ind['assessment_type'] ?? 'YES_NO') === 'YES_NO';
                $cat = $ind['category']['name'] ?? 'Lainnya';
                $groups[$cat] ??= ['id' => Str::slug($cat), 'title' => $cat, 'category_id' => $ind['category']['id'] ?? null, 'items' => []];
                $groups[$cat]['items'][] = [
                    'id' => $ind['id'],
                    'name' => $ind['name'],
                    'subtitle' => ($isYesNo ? 'Ceklis Ya/Tidak' : (($ind['calculation_type'] ?? '') === 'LOWER_IS_BETTER' ? 'Maksimal ' : 'Target ') . $fmt($pk['target']) . ' ' . ($ind['unit'] ?? ''))
                        . ' · Bobot ' . $fmt($pk['weight']) . '% · ' . (KelolaKpiService::FREQUENCIES[$pk['frequency']] ?? $pk['frequency'])
                        . (! empty($ind['requires_evidence']) ? ' · Wajib foto' : ''),
                    'badge' => $isYesNo ? 'Ceklis' : 'Angka · ' . ($ind['unit'] ?? ''),
                    // data untuk form Edit
                    'form' => [
                        'indicator_id' => $ind['id'],
                        'name' => $ind['name'],
                        'description' => $ind['description'] ?? '',
                        'category_id' => $ind['category']['id'] ?? null,
                        'assessment_type' => $ind['assessment_type'] ?? 'YES_NO',
                        'unit' => $isYesNo ? '' : ($ind['unit'] ?? ''),
                        'calculation_type' => $ind['calculation_type'] ?? 'HIGHER_IS_BETTER',
                        'requires_evidence' => (bool) ($ind['requires_evidence'] ?? false),
                        'target' => (float) $pk['target'],
                        'weight' => (float) $pk['weight'],
                        'frequency' => $pk['frequency'],
                    ],
                ];
            }
            foreach ($groups as &$g) {
                $g['count_text'] = count($g['items']) . ' hal';
            }
            unset($g);

            $label = $this->divisionLabel($pos['name']);
            $panels[] = [
                'position_id' => $pos['id'],
                'label' => $label,
                'slug' => Str::slug($label),
                'count' => count($kpis),
                'suggested_weight' => KelolaKpiService::suggestedWeight(count($kpis)),
                'groups' => array_values($groups),
                // Nonaktif = indikator di katalog yang tidak dipakai bagian ini (bisa diaktifkan lagi)
                'inactive' => array_values(array_map(fn ($i) => [
                    'id' => $i['id'],
                    'name' => $i['name'],
                    'category' => $i['category']['name'] ?? '-',
                    'is_active' => (bool) $i['is_active'],
                ], array_filter($catalog, fn ($i) => ! in_array((int) $i['id'], $assigned, true)))),
            ];
        }

        $active = (int) $request->query('bagian', $panels[0]['position_id'] ?? 0);
        $catalogOptions = array_map(fn ($i) => ['id' => $i['id'], 'name' => $i['name'], 'is_active' => (bool) $i['is_active']], $catalog);
        $frequencies = KelolaKpiService::FREQUENCIES;

        return view('admin.kpi', compact('user', 'panels', 'active', 'categories', 'catalogOptions', 'frequencies', 'unavailable'));
    }

    /** Tambah hal yang dicatat ke sebuah bagian: pakai indikator yang sudah ada atau buat baru. */
    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $positionId = (int) $data['position_id'];

        $indicatorId = $data['existing_id'] ?? null;
        if ($indicatorId) {
            // Pakai indikator yang sudah ada di daftar: ambil nama & jenisnya dari Backend
            $existing = BackendApiService::fetch("admin/kpi-indicators/{$indicatorId}")['data'] ?? null;
            if (! $existing) {
                return $this->fail(['first_error' => 'Hal yang dipilih tidak ditemukan di Backend.'], $positionId);
            }
            $data['name'] = $existing['name'];
            $data['assessment_type'] = $existing['assessment_type'];
        } else {
            $res = BackendApiService::send('POST', 'admin/kpi-indicators', $this->indicatorPayload($data));
            if (! $res['ok']) {
                return $this->fail($res, $positionId);
            }
            $indicatorId = $res['data']['id'] ?? null;
        }

        $current = KelolaKpiService::positionKpis($positionId);
        if ($current === null || ! $indicatorId) {
            return $this->fail(['first_error' => 'Backend tidak dapat dihubungi. Coba lagi.'], $positionId);
        }

        $res = KelolaKpiService::sync($positionId, $current, $this->positionItem($data, (int) $indicatorId));

        return $res['ok'] ? $this->done($positionId, "\"{$data['name']}\" ditambahkan.") : $this->fail($res, $positionId);
    }

    /** Ubah hal yang dicatat: data indikator (nama, jenis, satuan, foto) + target/bobot/frekuensi di bagian ini. */
    public function update(Request $request, int $indicator)
    {
        $data = $this->validated($request, false);
        $positionId = (int) $data['position_id'];

        $res = BackendApiService::send('PUT', "admin/kpi-indicators/{$indicator}", $this->indicatorPayload($data));
        if (! $res['ok']) {
            return $this->fail($res, $positionId);
        }

        $current = KelolaKpiService::positionKpis($positionId) ?? [];
        $res = KelolaKpiService::sync($positionId, $current, $this->positionItem($data, $indicator));

        return $res['ok'] ? $this->done($positionId, "\"{$data['name']}\" diperbarui.") : $this->fail($res, $positionId);
    }

    /** Pindahkan ke Nonaktif: lepas dari bagian ini (indikator tetap ada di katalog). */
    public function nonaktifkan(Request $request, int $indicator)
    {
        $positionId = (int) $request->validate(['position_id' => ['required', 'integer']])['position_id'];
        $res = KelolaKpiService::sync($positionId, KelolaKpiService::positionKpis($positionId) ?? [], null, $indicator);

        return $res['ok'] ? $this->done($positionId, 'Hal dipindahkan ke Nonaktif. Bobot lain disesuaikan agar total 100%.') : $this->fail($res, $positionId);
    }

    /** Aktifkan lagi: pasang kembali indikator dari daftar Nonaktif ke bagian ini. */
    public function aktifkan(Request $request, int $indicator)
    {
        $positionId = (int) $request->validate(['position_id' => ['required', 'integer']])['position_id'];
        $current = KelolaKpiService::positionKpis($positionId) ?? [];

        $res = KelolaKpiService::sync($positionId, $current, [
            'kpi_indicator_id' => $indicator,
            'target' => 1,
            'weight' => KelolaKpiService::suggestedWeight(count($current)),
            'frequency' => 'DAILY',
        ]);

        return $res['ok'] ? $this->done($positionId, 'Hal diaktifkan kembali di bagian ini (frekuensi Harian, ubah lewat Edit bila perlu).') : $this->fail($res, $positionId);
    }

    /** Hapus permanen: lepas dari bagian ini, lalu hapus dari katalog bila tidak dipakai bagian lain. */
    public function hapus(Request $request, int $indicator)
    {
        $positionId = (int) $request->validate(['position_id' => ['required', 'integer']])['position_id'];
        $current = KelolaKpiService::positionKpis($positionId) ?? [];

        if (collect($current)->contains(fn ($pk) => (int) $pk['indicator']['id'] === $indicator)) {
            $res = KelolaKpiService::sync($positionId, $current, null, $indicator);
            if (! $res['ok']) {
                return $this->fail($res, $positionId);
            }
        }

        // Jumlah bagian yang masih memakai hal ini (hanya tersedia di daftar katalog, bukan di detail)
        $usedElsewhere = (int) (collect(KelolaKpiService::catalog())->firstWhere('id', $indicator)['position_kpis_count'] ?? 0);
        if ($usedElsewhere > 0) {
            return $this->done($positionId, "Hal dilepas dari bagian ini. Tidak dihapus dari daftar karena masih dipakai {$usedElsewhere} bagian lain.");
        }

        $res = BackendApiService::send('DELETE', "admin/kpi-indicators/{$indicator}");
        if (! $res['ok']) {
            return $this->fail($res, $positionId);
        }

        return $this->done($positionId, ($res['data']['result'] ?? '') === 'deactivated'
            ? 'Hal ini sudah punya riwayat capaian, jadi dinonaktifkan (riwayat tetap tersimpan).'
            : 'Hal berhasil dihapus permanen.');
    }

    // ---------------------------------------------------------------------

    private function validated(Request $request, bool $isNew): array
    {
        $existing = $isNew && $request->filled('existing_id');

        return $request->validate([
            'position_id' => ['required', 'integer'],
            'existing_id' => ['nullable', 'integer'],
            'name' => [$existing ? 'nullable' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'category_id' => [$existing ? 'nullable' : 'required', 'integer'],
            'assessment_type' => [$existing ? 'nullable' : 'required', 'in:YES_NO,NUMERIC'],
            'unit' => ['nullable', 'string', 'max:50'],
            'calculation_type' => ['nullable', 'in:HIGHER_IS_BETTER,LOWER_IS_BETTER'],
            'requires_evidence' => ['nullable', 'boolean'],
            'target' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'frequency' => ['required', 'in:' . implode(',', array_keys(KelolaKpiService::FREQUENCIES))],
        ], [
            'name.required' => 'Nama hal yang dicatat wajib diisi.',
            'category_id.required' => 'Pilih kelompok.',
            'weight.required' => 'Bobot wajib diisi.',
            'weight.min' => 'Bobot minimal 0,01%.',
        ]);
    }

    private function indicatorPayload(array $d): array
    {
        $isYesNo = $d['assessment_type'] === 'YES_NO';

        return [
            'name' => $d['name'],
            'description' => $d['description'] ?? null,
            'category_id' => (int) $d['category_id'],
            'assessment_type' => $d['assessment_type'],
            'unit' => $isYesNo ? 'ya/tidak' : ($d['unit'] ?: null),
            'calculation_type' => $d['calculation_type'] ?? 'HIGHER_IS_BETTER',
            'requires_evidence' => (bool) ($d['requires_evidence'] ?? false),
        ];
    }

    private function positionItem(array $d, int $indicatorId): array
    {
        return [
            'kpi_indicator_id' => $indicatorId,
            'target' => ($d['assessment_type'] ?? 'YES_NO') === 'NUMERIC' ? (float) ($d['target'] ?? 0) : 1,
            'weight' => (float) $d['weight'],
            'frequency' => $d['frequency'],
        ];
    }

    private function divisionLabel(string $position): string
    {
        return match (strtolower($position)) {
            'sales' => 'Sales & Marketing',
            'spv' => 'Supervisor',
            default => $position,
        };
    }

    private function done(int $positionId, string $message)
    {
        return redirect()->route('admin.kpi', ['bagian' => $positionId])->with('status', $message);
    }

    private function fail(array $res, int $positionId)
    {
        return redirect()->route('admin.kpi', ['bagian' => $positionId])->withInput()
            ->with('error', $res['first_error'] ?? $res['message'] ?? 'Permintaan gagal diproses Backend.');
    }
}
