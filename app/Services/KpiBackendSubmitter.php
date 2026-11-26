<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Kirim capaian dari form Isi Capaian ke Backend (POST /api/my-kpis/submissions).
 *
 * Form dibuat otomatis dari indikator jabatan di Backend (lihat KpiFormController), sehingga setiap
 * isian langsung = satu indikator: ceklis -> answer_yes_no, angka -> numeric_value.
 * Foto bukti diteruskan ke POST /api/kpi-submissions/{id}/evidences.
 */
class KpiBackendSubmitter
{
    private const PHOTO_DIR = 'kpi-upload';

    /**
     * Simpan sementara foto dari request (field $field) untuk indikator $code sampai laporan dikirim.
     * Foto yang dipilih ulang untuk indikator yang sama menggantikan pilihan sebelumnya.
     */
    public static function stashPhotos(Request $request, string $code, string $field = 'photos'): void
    {
        $files = array_filter((array) $request->file($field, []), fn ($f) => $f && $f->isValid()
            && in_array(strtolower($f->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp'], true));
        if (! $files) {
            return;
        }

        self::forgetPhotos($code);
        // Folder per indikator: foto yang sama bisa dipakai beberapa indikator wajib foto di satu langkah
        $dir = self::PHOTO_DIR . '/' . session()->getId() . '/' . $code;
        $stored = [];
        foreach (array_slice($files, 0, 5) as $file) {
            $stored[] = [
                'path' => $file->store($dir),
                'name' => $file->getClientOriginalName(),
            ];
        }
        session(["kpi_photos.{$code}" => $stored]);
    }

    /** Jumlah foto yang sudah dipilih (belum terkirim) untuk indikator $code. */
    public static function stashedCount(string $code): int
    {
        return count(session("kpi_photos.{$code}", []));
    }

    private static function forgetPhotos(string $code): void
    {
        foreach (session("kpi_photos.{$code}", []) as $photo) {
            Storage::delete($photo['path']);
        }
        session()->forget("kpi_photos.{$code}");
    }

    /**
     * Kirim isian ke Backend.
     *
     * @param  array<int, array>  $items   [{position_kpi_id, answer_yes_no|numeric_value}]
     * @param  array<string, array>  $byCode  KPI jabatan dari GET /api/my-kpis, dikelompokkan per kode indikator
     * @param  bool  $submit  false = simpan sebagai draf saja
     */
    public static function send(array $items, array $byCode, bool $submit): array
    {
        if (! $items) {
            return ['ok' => false, 'message' => 'Tidak ada isian yang bisa dikirim. Capaian periode ini sudah dikirim dan sedang/selesai ditinjau.', 'first_error' => null];
        }

        // Revisi: foto baru menggantikan foto lama (Backend maks. 5 foto per indikator).
        // Dilakukan sebelum disimpan ulang sebagai draf, karena riwayat Backend tidak memuat isian draf.
        if ($submit) {
            foreach (array_keys(array_filter(session('kpi_photos', []))) as $code) {
                $old = $byCode[$code]['submission'] ?? null;
                if ($old && (int) ($old['evidences_count'] ?? 0) > 0) {
                    self::deleteOldEvidences((int) $old['id'], $old['report_date'] ?? null);
                }
            }
        }

        // 1) Simpan sebagai draf untuk mendapatkan id capaian, 2) unggah foto bukti, 3) kirim
        $draft = BackendApiService::send('POST', 'my-kpis/submissions', ['items' => $items, 'submit' => false]);
        if (! $draft['ok'] || ! $submit) {
            return $draft;
        }

        $uploadError = self::uploadPhotos($draft['data'] ?? [], $byCode);
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

            foreach (array_slice($photos, 0, 5) as $photo) {
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

    /**
     * Hapus foto bukti lama sebuah capaian (DELETE /api/kpi-submissions/{id}/evidences/{evidence}).
     * Id foto diambil dari detail riwayat tanggal laporan itu.
     */
    private static function deleteOldEvidences(int $submissionId, ?string $reportDate): void
    {
        if (! $reportDate) {
            return;
        }

        $detail = BackendApiService::fetch('my-kpis/history/' . substr($reportDate, 0, 10))['data'] ?? [];
        $indicators = collect($detail['categories'] ?? [])->flatMap(fn ($c) => $c['indicators'] ?? [])
            ->merge($detail['indicators'] ?? []);

        foreach ($indicators->firstWhere('id', $submissionId)['evidences'] ?? [] as $evidence) {
            BackendApiService::send('DELETE', "kpi-submissions/{$submissionId}/evidences/{$evidence['id']}");
        }
    }
}
