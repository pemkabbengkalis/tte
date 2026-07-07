<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'aktif' => EnsureUserIsActive::class,
        ]);

        $middleware->append(SecurityHeaders::class);
    
        $middleware->redirectUsersTo('/dashboard');

        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Defense-in-depth terhadap Information Disclosure: mencegah bocornya path
        // filesystem/nama exception framework pada response JSON (Livewire/API) di
        // luar environment local/testing, meskipun APP_DEBUG tidak sengaja aktif.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (app()->environment(['local', 'testing'])) {
                return $response;
            }

            $content = json_decode($response->getContent(), true);

            if (is_array($content) && array_intersect(['exception', 'file', 'line', 'trace'], array_keys($content))) {
                return response()->json([
                    'message' => $content['message'] ?? 'Terjadi kesalahan pada server.',
                ], $response->getStatusCode());
            }

            return $response;
        });
    })->create();
