<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Tanda "sudah ditegur / ditindaklanjuti manual" pada halaman Tinjau Revisi Admin.
 * Backend belum menyediakan fitur ini, jadi disimpan di FE (storage/app/revisi_tindak_lanjut.json).
 * Kunci memuat waktu penolakan, sehingga penolakan baru pada laporan yang sama muncul lagi.
 */
class RevisiTindakLanjutService
{
    private const FILE = 'revisi_tindak_lanjut.json';

    public static function semua(): array
    {
        return Storage::exists(self::FILE) ? (json_decode(Storage::get(self::FILE), true) ?: []) : [];
    }

    public static function tandai(string $key): void
    {
        $list = self::semua();
        $list[] = $key;
        Storage::put(self::FILE, json_encode(array_values(array_unique(array_slice($list, -1000))), JSON_PRETTY_PRINT));
    }
}
