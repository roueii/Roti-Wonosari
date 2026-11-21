<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * "Status Akun Aktif" (Figma Kelola Akun): izin karyawan untuk login & mengisi KPI harian.
 *
 * Berbeda dengan "Nonaktifkan Akun Karyawan" (is_active di Backend = akun diblokir/banned),
 * status ini hanya penangguhan sementara yang bisa dinyalakan lagi kapan saja.
 * Backend belum punya kolom untuk ini, jadi disimpan sementara di FE
 * (storage/app/akses_akun.json, kunci = email huruf kecil). Daftar berisi email yang DIMATIKAN aksesnya.
 */
class AksesAkunService
{
    private const FILE = 'akses_akun.json';

    public const PESAN_DIBATASI = 'Akses akun Anda sedang dinonaktifkan oleh admin. Anda belum bisa masuk dan mengisi KPI harian.';

    private static function load(): array
    {
        if (! Storage::exists(self::FILE)) {
            return [];
        }

        return json_decode(Storage::get(self::FILE), true) ?: [];
    }

    private static function save(array $list): void
    {
        Storage::put(self::FILE, json_encode(array_values(array_unique($list)), JSON_PRETTY_PRINT));
    }

    private static function key(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    public static function bisaAkses(?string $email): bool
    {
        return ! in_array(self::key($email), self::load(), true);
    }

    public static function atur(string $email, bool $bisaAkses): void
    {
        $key = self::key($email);
        $list = array_filter(self::load(), fn ($e) => $e !== $key);
        if (! $bisaAkses) {
            $list[] = $key;
        }
        self::save($list);
    }

    /** Pindahkan status bila email akun diubah admin. */
    public static function gantiEmail(string $lama, string $baru): void
    {
        if (self::key($lama) === self::key($baru) || self::bisaAkses($lama)) {
            return;
        }
        self::atur($lama, true);
        self::atur($baru, false);
    }
}
