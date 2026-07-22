<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaApiLoad&render=explicit" async defer></script>
</head>

<body class="font-sans antialiased">
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-primary-800 to-primary-900 px-4 py-10">
        <div class="mb-6 text-center">
            <div
                class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-white/10 text-xl font-bold text-white ring-1 ring-white/20">
                SE
            </div>
            <h1 class="text-lg font-semibold text-white">Sertifikat Elektronik</h1>
            <p class="text-sm text-primary-200">Dinas Kominfotik Kabupaten Bengkalis</p>
        </div>

        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl sm:p-8">
            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-xs text-primary-200">
            &copy; {{ date('Y') }} Pemerintah Kabupaten Bengkalis
        </p>
    </div>

    @if (session('status') === 'registered')
        <x-toast message="Pendaftaran berhasil. Akun Anda menunggu aktivasi oleh petugas sebelum dapat digunakan." type="info" />
    @endif

    @livewireScripts

    <script>
        function onRecaptchaApiLoad() {
            renderRecaptchaWidgets();
        }

        function renderRecaptchaWidgets() {
            if (typeof grecaptcha === 'undefined' || !grecaptcha.render) {
                return;
            }

            document.querySelectorAll('.g-recaptcha:not([data-widget-id])').forEach((el) => {
                const widgetId = grecaptcha.render(el, {
                    sitekey: el.dataset.sitekey,
                    callback: (token) => {
                        el.dispatchEvent(new CustomEvent('recaptcha:verified', { detail: token, bubbles: true }));
                    },
                    'expired-callback': () => {
                        el.dispatchEvent(new CustomEvent('recaptcha:verified', { detail: '', bubbles: true }));
                    },
                });
                el.setAttribute('data-widget-id', widgetId);
            });
        }

        document.addEventListener('livewire:navigated', renderRecaptchaWidgets);

        document.addEventListener('livewire:init', () => {
            Livewire.on('recaptcha-reset', () => {
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
            });
        });
    </script>
</body>

</html>
