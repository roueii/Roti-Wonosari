<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Kirim capaian harian dari form FE ke Backend (POST /api/my-kpis/submissions).
 *
 * Form FE (Figma) berisi item ceklis rinci, sedangkan indikator Backend bersifat ringkasan.
 * Setiap role memetakan item FE -> KODE indikator Backend; beberapa item boleh menunjuk kode yang sama
 * dan indikator itu bernilai "Ya" hanya bila SEMUA item terkaitnya dicentang.
 * Foto bukti yang diunggah di form diteruskan ke POST /api/kpi-submissions/{id}/evidences.
 */
class KpiBackendSubmitter
{
    private const PHOTO_DIR = 'kpi-upload';

    /**
     * Simpan sementara foto dari request (field $field) untuk indikator $code sampai laporan dikirim.
     */
    public static function stashPhotos(Request $request, string $code, string $field = 'photos'): void
    {
        $files = array_filter((array) $request->file($field, []), fn ($f) => $f && $f->isValid()
            && in_array(strtolower($f->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp'], true));
        if (! $files) {
            return;
        }

        $dir = self::PHOTO_DIR . '/' . session()->getId();
        $stored = session("kpi_photos.{$code}", []);
        foreach ($files as $file) {
            $stored[] = [
                'path' => $file->store($dir),
                'name' => $file->getClientOriginalName(),
            ];
        }
        session(["kpi_photos.{$code}" => array_slice($stored, -5)]);
    }

    /**
     * @param  array<string, string>  $map          item key FE => kode indikator Backend
     * @param  array<int, string>     $checkedKeys  item yang dicentang (dari semua langkah)
     * @param  array<int, string>|null $onlyKeys    batasi ke item ini (mis. draf satu langkah); null = semua
     * @return array|null  hasil BackendApiService::send, atau null bila sesi tidak terhubung ke Backend
     */
    public static function submit(array $map, array $checkedKeys, bool $submit = true, ?array $onlyKeys = null): ?array
    {
        if (! $map || ! BackendApiService::connected()) {
            return null;
        }

        $kpis = BackendApiService::fetch('my-kpis')['data'] ?? null;
        if (! is_array($kpis)) {
            return ['ok' => false, 'message' => 'Data KPI dari Backend tidak dapat dimuat. Coba lagi.', 'first_error' => null];
        }
        $byCode = collect($kpis)->filter(fn ($pk) => ! empty($pk['indicator']['code']))
            ->keyBy(fn ($pk) => $pk['indicator']['code']);

        $keys = $onlyKeys ?? array_keys($map);
        $groups = [];
        foreach ($map as $key => $code) {
            $groups[$code][] = $key;
        }

        $items = [];
        foreach ($groups as $code => $codeKeys) {
            $pk = $byCode[$code] ?? null;
            if (! $pk || array_diff($codeKeys, $keys)
                || in_array($pk['submission']['status'] ?? null, ['SUBMITTED', 'APPROVED'], true)) {
                continue;
            }
            $done = ! array_diff($codeKeys, $checkedKeys);
            $items[] = ($pk['input_type'] ?? 'YES_NO') === 'YES_NO'
                ? ['position_kpi_id' => $pk['id'], 'answer_yes_no' => $done]
                : ['position_kpi_id' => $pk['id'], 'numeric_value' => $done ? (float) ($pk['target'] ?: 1) : 0];
        }

        if (! $items) {
            return $submit
                ? ['ok' => false, 'message' => 'Capaian periode ini sudah dikirim ke Backend dan menunggu/selesai direview.', 'first_error' => null]
                : null;
        }

        // 1) Simpan sebagai draf untuk mendapatkan id capaian, 2) unggah foto bukti, 3) kirim
        $draft = BackendApiService::send('POST', 'my-kpis/submissions', ['items' => $items, 'submit' => false]);
        if (! $draft['ok'] || ! $submit) {
            return $draft;
        }

        $uploadError = self::uploadPhotos($draft['data'] ?? [], $byCode->all());
        if ($uploadError) {
            return ['ok' => false, 'message' => $uploadError, 'first_error' => $uploadError];
        }

        return BackendApiService::send('POST', 'my-kpis/submissions', ['items' => $items, 'submit' => true]);
    }

    /** Unggah foto yang disimpan sementara ke capaian Backend yang sesuai. Mengembalikan pesan error bila gagal. */
    private static function uploadPhotos(array $saved, array $byCode): ?string
    {
        $idByPk = collect($saved)->pluck('id', 'position_kpi_id');

        foreach (session('kpi_photos', []) as $code => $photos) {
            $pk = $byCode[$code] ?? null;
            $submissionId = $pk ? ($idByPk[$pk['id']] ?? null) : null;
            if (! $submissionId) {
                continue;
            }

            $remaining = max(0, 5 - (int) ($pk['submission']['evidences_count'] ?? 0));
            foreach (array_slice($photos, 0, $remaining) as $photo) {
                if (! Storage::exists($photo['path'])) {
                    continue;
                }
                try {
                    $res = BackendApiService::client()
                        ->attach('file', Storage::get($photo['path']), $photo['name'])
                        ->post(BackendApiService::url("kpi-submissions/{$submissionId}/evidences"));
                } catch (\Throwable $e) {
                    return 'Foto bukti gagal diunggah ke Backend. Coba kirim lagi.';
                }
                if (! $res->successful()) {
                    return $res->json('errors.file.0') ?? $res->json('message') ?? 'Foto bukti gagal diunggah ke Backend.';
                }
                Storage::delete($photo['path']);
            }
            session()->forget("kpi_photos.{$code}");
        }

        return null;
    }
}
