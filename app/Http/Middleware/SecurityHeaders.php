<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Header dasar — aman di semua environment
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // HSTS hanya masuk akal saat koneksi memang sudah HTTPS — digantungkan pada
        // skema request, bukan nama environment, supaya tidak lolos akibat APP_ENV
        // yang salah konfigurasi di server produksi.
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Saat Vite dev server aktif (npm run dev, ditandai file public/hot), aset
        // dimuat cross-origin dari server itu (mis. http://[::1]:5173), bukan dari
        // 'self'. File hot ini tidak pernah ada di build produksi, jadi penambahan
        // origin ini otomatis tidak berlaku di production.
        $viteDevServer = null;
        if (! app()->environment('production') && file_exists(public_path('hot'))) {
            $viteDevServer = trim(file_get_contents(public_path('hot')));
        }
        $viteWs = $viteDevServer ? preg_replace('#^http#', 'ws', $viteDevServer) : null;

        // CSP diterapkan pada semua environment sebagai defense-in-depth terhadap XSS,
        // bukan hanya saat app()->environment('production') (yang sebelumnya membuat
        // header ini tidak pernah terkirim ketika APP_ENV tidak diset ke 'production').
        $csp = "default-src 'self'; "
            . "script-src 'self' 'unsafe-eval' 'unsafe-inline'" . ($viteDevServer ? " {$viteDevServer}" : '') . "; "
            . "style-src 'self' 'unsafe-inline'" . ($viteDevServer ? " {$viteDevServer}" : '') . "; "
            . "img-src 'self' data: blob:" . ($viteDevServer ? " {$viteDevServer}" : '') . "; "
            . "font-src 'self' data:" . ($viteDevServer ? " {$viteDevServer}" : '') . "; "
            . "connect-src 'self'" . ($viteDevServer ? " {$viteDevServer} {$viteWs}" : '') . "; "
            . "media-src 'self'; "
            . "object-src 'none'; "
            . "form-action 'self'; "
            . "base-uri 'self'; "
            . "frame-ancestors 'none'";

        // upgrade-insecure-requests menyuruh browser memaksa semua request di halaman
        // ini ke HTTPS. Sama seperti HSTS, ini harus digantungkan pada koneksi yang
        // memang sudah HTTPS — kalau tidak, di server dev plain-HTTP (mis. php artisan
        // serve) browser akan mencoba TLS handshake ke server yang tidak bisa
        // menanganinya ("Unsupported SSL request"), sehingga request (termasuk login)
        // gagal.
        if ($request->secure()) {
            $csp .= "; upgrade-insecure-requests";
        }

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}