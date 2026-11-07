<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\ForceJsonResponse;
use App\Support\DbErrorReason;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // /health didaftarkan di luar grup middleware web/api: tanpa sesi,
            // tanpa cookie, dan tanpa token, karena dipanggil healthcheck Docker.
            Route::get('/health', HealthController::class)->name('health');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Semua route /api/* selalu dibalas sebagai JSON.
        $middleware->api(prepend: [ForceJsonResponse::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Validasi gagal dicatat di SATU tempat. Yang dicatat hanya NAMA kolom
        // yang bermasalah, tidak pernah nilai yang diketik pengguna.
        $exceptions->render(function (ValidationException $e, Request $request) {
            Log::warning('Validasi gagal', [
                'event' => 'validation.failed',
                'user_id' => $request->user()?->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'failed_fields' => array_keys($e->errors()),
            ]);

            // Tidak mengembalikan apa pun: balasan 422 JSON bawaan Laravel tetap berjalan.
        });

        // Galat basis data dicatat dengan alasan dan kode saja. Pesan asli
        // QueryException memuat SQL beserta nilai yang diikat (bisa berisi email atau
        // data pengguna), jadi pencatatan bawaan dihentikan dengan ->stop().
        $exceptions->report(function (QueryException $e) {
            Log::error('Gagal mengakses basis data', [
                'event' => 'db.query_failed',
                'reason' => DbErrorReason::reason($e),
                'error_code' => DbErrorReason::code($e),
            ]);
        })->stop();
    })->create();
