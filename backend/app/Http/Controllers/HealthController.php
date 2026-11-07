<?php

namespace App\Http\Controllers;

use App\Support\DbErrorReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * GET /health: benar-benar memeriksa basis data dan cache.
 * 200 bila semuanya sehat, 503 bila ada yang bermasalah, dan balasannya
 * menyebut BAGIAN MANA yang bermasalah beserta alasannya.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $results = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $healthy = true;
        $checks = [];

        foreach ($results as $component => $result) {
            if ($result['status'] !== 'ok') {
                $healthy = false;

                Log::error('Pemeriksaan kesehatan gagal', [
                    'event' => 'health.check_failed',
                    'component' => $component,
                    'reason' => $result['reason'],
                    'error_code' => $result['error_code'] ?? null,
                ]);
            }

            // error_code hanya untuk log; klien cukup melihat alasannya.
            unset($result['error_code']);
            $checks[$component] = $result;
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    /** Menjalankan query sungguhan ke basis data. */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::select('select 1');

            return $this->ok($start);
        } catch (Throwable $e) {
            return [
                'status' => 'down',
                'reason' => DbErrorReason::reason($e),
                'error_code' => DbErrorReason::code($e),
            ];
        }
    }

    /** Menulis lalu membaca kembali satu nilai di cache. */
    private function checkCache(): array
    {
        $start = microtime(true);
        $key = 'health:probe';
        $value = bin2hex(random_bytes(4));

        try {
            Cache::put($key, $value, 10);

            if (Cache::get($key) !== $value) {
                throw new RuntimeException('cache read mismatch');
            }

            Cache::forget($key);

            return $this->ok($start);
        } catch (Throwable $e) {
            return [
                'status' => 'down',
                'reason' => 'cache_unavailable',
                'error_code' => null,
            ];
        }
    }

    private function ok(float $start): array
    {
        return [
            'status' => 'ok',
            'latency_ms' => round((microtime(true) - $start) * 1000, 1),
        ];
    }
}
