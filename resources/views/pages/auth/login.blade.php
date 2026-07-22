<?php

use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $email = '';
    public string $password = '';
    public string $recaptchaToken = '';

    protected function rules(): array
    {
        return [
            'email'          => ['required', 'string', 'email'],
            'password'       => ['required', 'string'],
            'recaptchaToken' => ['required', new Recaptcha()],
        ];
    }

    public function rendering(): void
    {
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->reset('recaptchaToken');
            $this->dispatch('recaptcha-reset');
        }
    }

    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->email)->first();

        if (!$user || !Hash::check($this->password, $user->password)) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        if (!$user->isAktif()) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda belum diaktifkan oleh petugas. Silakan hubungi Dinas Kominfotik.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return 'login:' . request()->ip();
    }
};

?>

<div>
    <div class="mb-6 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Masuk</h2>
        <p class="mt-1 text-sm text-gray-500">Sistem Permohonan Sertifikat Elektronik</p>
    </div>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi</label>
            <div class="relative">
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'"
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <button type="button" @click="show = !show" tabindex="-1"
                    :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                    <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div wire:ignore x-on:recaptcha:verified.window="$wire.set('recaptchaToken', $event.detail)">
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
        </div>
        @error('recaptchaToken')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror

        <button type="submit" wire:loading.attr="disabled" wire:target="login"
            class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60">
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses...</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-gray-500">
        Belum punya akun?
        <a href="{{ route('register') }}" wire:navigate
            class="font-medium text-primary-600 hover:text-primary-700">Daftar di sini</a>
    </p>
</div>
