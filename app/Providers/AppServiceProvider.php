<?php

namespace App\Providers;

use App\Models\DokumenPermohonan;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\User;
use App\Policies\DokumenPolicy;
use App\Policies\NotifikasiPolicy;
use App\Policies\PermohonanPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    // Pastikan format tanggal (nama hari/bulan) mengikuti lokal Indonesia.
        \Carbon\Carbon::setLocale(config('app.locale'));

        Gate::policy(Permohonan::class, PermohonanPolicy::class);
        Gate::policy(DokumenPermohonan::class, DokumenPolicy::class);
        Gate::policy(Notifikasi::class, NotifikasiPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(30)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
