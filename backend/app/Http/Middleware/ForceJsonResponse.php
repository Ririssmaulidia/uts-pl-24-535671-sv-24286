<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memaksa semua permintaan API diperlakukan sebagai permintaan JSON,
 * sehingga galat validasi (422) dan token tidak ada (401) selalu
 * dibalas dalam bentuk JSON, bukan pengalihan (redirect) HTML.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
