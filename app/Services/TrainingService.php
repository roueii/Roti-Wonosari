<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Catatan Training Karyawan oleh Store Manager.
 * Backend belum menyediakan fitur training, jadi disimpan di FE (storage/app/sm_trainings.json),
 * dikelompokkan per akun Store Manager agar tidak hilang saat logout.
 */
class TrainingService
{
    private const FILE = 'sm_trainings.json';

    private static function semua(): array
    {
        return Storage::exists(self::FILE) ? (json_decode(Storage::get(self::FILE), true) ?: []) : [];
    }

    public static function milik(string $owner): array
    {
        return self::semua()[$owner] ?? [];
    }

    public static function tambah(string $owner, array $training): void
    {
        $all = self::semua();
        $all[$owner][] = $training;
        Storage::put(self::FILE, json_encode($all, JSON_PRETTY_PRINT));
    }
}
