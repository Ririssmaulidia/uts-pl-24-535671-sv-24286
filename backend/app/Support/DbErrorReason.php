<?php

namespace App\Support;

use Throwable;

/**
 * Mengubah galat basis data menjadi ALASAN singkat yang aman dicatat.
 *
 * Pesan asli galat basis data (QueryException) memuat SQL beserta nilai
 * yang diikat, misalnya email pengguna atau judul pengeluaran. Karena itu
 * pesan asli TIDAK PERNAH dicatat atau ditampilkan; yang dipakai hanya
 * kode galat dan alasan hasil pemetaan di bawah ini.
 */
class DbErrorReason
{
    private const BY_CODE = [
        '2002' => 'database_unreachable',   // koneksi ditolak / host tidak ditemukan
        '2006' => 'database_unreachable',   // server basis data hilang
        '2013' => 'database_unreachable',   // koneksi putus saat query
        '1045' => 'access_denied',          // nama pengguna / kata sandi basis data salah
        '1049' => 'unknown_database',       // nama basis data tidak ada
        '1146' => 'table_missing',          // tabel belum dimigrasi
        '23000' => 'constraint_violation',  // SQLSTATE pelanggaran constraint
    ];

    /** Kode galat (kode driver atau SQLSTATE), tanpa pesan asli. */
    public static function code(Throwable $e): ?string
    {
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            $code = (string) $current->getCode();

            if ($code !== '' && $code !== '0') {
                return $code;
            }
        }

        return null;
    }

    /** Alasan singkat yang aman dicatat dan boleh dilihat klien. */
    public static function reason(Throwable $e): string
    {
        $code = self::code($e);

        if ($code !== null && isset(self::BY_CODE[$code])) {
            return self::BY_CODE[$code];
        }

        // Cadangan: pesan hanya dipakai untuk MENGGOLONGKAN, tidak pernah dicatat.
        $message = strtolower($e->getMessage());

        foreach (['getaddrinfo', 'connection refused', 'no route to host', 'server has gone away', 'timed out'] as $needle) {
            if (str_contains($message, $needle)) {
                return 'database_unreachable';
            }
        }

        return 'database_error';
    }
}
