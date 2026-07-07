<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased bg-gray-100 text-gray-800">
    @php
        $user = auth()->user();
    @endphp

    <div x-data="{ sidebarOpen: false, userMenu: false }" class="min-h-screen">

        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/40 lg:hidden" style="display: none;"></div>

        {{-- ===== SIDEBAR ===== --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 flex w-64 transform flex-col bg-primary-800 transition-transform duration-200 ease-in-out lg:translate-x-0">
            <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-sm font-bold text-white ring-1 ring-white/20">
                    SE
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-white">Sertifikat Elektronik</p>
                    <p class="text-[11px] text-primary-200">Diskominfotik Bengkalis</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @if ($user?->isPemohon())
                    <x-sidebar-link href="/pemohon" :active="['pemohon', 'pemohon/permohonan/*']">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </x-sidebar-link>
                    <x-sidebar-link href="/pemohon/buat-permohonan">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Buat Permohonan
                    </x-sidebar-link>
                @endif

                @if ($user?->isVerifikator())
                    <x-sidebar-link href="/verifikator" :active="['verifikator', 'verifikator/permohonan/*']">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        Daftar Permohonan
                    </x-sidebar-link>
                    <x-sidebar-link href="/verifikator/template">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Kelola Template
                    </x-sidebar-link>
                    <x-sidebar-link href="/akun">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Aktivasi Akun
                    </x-sidebar-link>
                @endif

                @if ($user?->isAdmin())
                    <x-sidebar-link href="/admin" :exact="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </x-sidebar-link>
                    <x-sidebar-link href="/admin/pengguna">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Kelola Pengguna
                    </x-sidebar-link>
                    <x-sidebar-link href="/admin/template">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Kelola Template
                    </x-sidebar-link>
                @endif
            </nav>

            <div class="border-t border-white/10 p-4">
                <p class="text-[11px] text-primary-300">v1.0 &middot; {{ date('Y') }}</p>
            </div>
        </aside>

        {{-- ===== KOLOM UTAMA ===== --}}
        <div class="lg:pl-64">

            <header
                class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 lg:px-6">
                <button @click="sidebarOpen = true" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden"
                    aria-label="Buka menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="flex-1 min-w-0">
                    @if (!empty($user?->instansi))
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="hidden h-5 w-5 shrink-0 text-gray-400 sm:block" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-14h.01M11 7h.01M7 11h.01M11 11h.01M7 15h.01M11 15h.01" />
                            </svg>
                            <span class="truncate text-lg font-medium">{{ $user->instansi }}</span>
                        </div>
                    @else
                        <span class="text-sm italic text-gray-400">Instansi belum diatur</span>
                    @endif
                </div>

                @auth
                    <livewire:components.notification-bell />
                @endauth

                <div class="relative" @click.outside="userMenu = false">
                    <button @click="userMenu = !userMenu"
                        class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-gray-100">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-semibold text-white">
                            {{ \Illuminate\Support\Str::of($user?->nama_lengkap ?? 'U')->substr(0, 1)->upper() }}
                        </span>
                        <span class="hidden text-left sm:block">
                            <span
                                class="block text-sm font-medium leading-tight text-gray-800">{{ $user?->nama_lengkap ?? 'Pengguna' }}</span>
                            <span
                                class="block text-[11px] leading-tight text-gray-400">{{ $user?->role?->label() ?? '-' }}</span>
                        </span>
                        <svg class="hidden h-4 w-4 text-gray-400 sm:block" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="userMenu" x-transition
                        class="absolute right-0 mt-2 w-52 origin-top-right rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                        style="display: none;">
                        <div class="border-b border-gray-100 px-4 py-2 sm:hidden">
                            <p class="text-sm font-medium text-gray-800">{{ $user?->nama_lengkap ?? 'Pengguna' }}</p>
                            <p class="text-xs text-gray-400">{{ $user?->role?->label() ?? '-' }}</p>
                        </div>

                        <a href="{{ route('profile.index') }}" wire:navigate
                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profil Saya
                        </a>

                        <div class="my-1 border-t border-gray-100"></div>

                        <form method="POST" action="{{ url('/logout') }}">
                            @csrf
                            <button type="submit"
                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Global Toast (dipicu oleh event Livewire) --}}
    <div
        x-data="{ toasts: [] }"
        @toast.window="
            const id = Date.now();
            toasts.push({ id, message: $event.detail.message, type: $event.detail.type ?? 'success' });
            setTimeout(() => { toasts = toasts.filter(t => t.id !== id) }, 5500)
        "
        class="fixed bottom-5 right-5 z-50 flex flex-col gap-3"
        aria-live="polite"
    >
        <template x-for="t in toasts" :key="t.id">
            <div
                x-data="{ show: true, width: 100 }"
                x-init="
                    let tick = setInterval(() => { width = Math.max(0, width - 2) }, 100);
                    setTimeout(() => { show = false; clearInterval(tick) }, 5000);
                "
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-3 scale-95"
                class="w-80 overflow-hidden rounded-xl bg-white shadow-xl ring-1"
                :class="{
                    'ring-green-200': t.type === 'success',
                    'ring-red-200': t.type === 'error',
                    'ring-blue-200': t.type === 'info',
                    'ring-yellow-200': t.type === 'warning',
                }"
                role="alert"
            >
                <div class="flex items-start gap-3 px-4 py-3.5">
                    <div
                        class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                        :class="{
                            'bg-green-100': t.type === 'success',
                            'bg-red-100': t.type === 'error',
                            'bg-blue-100': t.type === 'info',
                            'bg-yellow-100': t.type === 'warning',
                        }"
                    >
                        <template x-if="t.type === 'success'">
                            <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="t.type === 'error'">
                            <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </template>
                        <template x-if="t.type === 'info'">
                            <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5h2M12 9v7m9-4a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                        <template x-if="t.type === 'warning'">
                            <svg class="h-4 w-4 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </template>
                    </div>

                    <p class="flex-1 pt-0.5 text-sm leading-snug text-gray-700" x-text="t.message"></p>

                    <button
                        @click="show = false"
                        class="mt-0.5 shrink-0 rounded p-0.5 text-gray-400 transition hover:text-gray-600"
                        aria-label="Tutup"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="h-0.5 bg-gray-100">
                    <div
                        class="h-full transition-none"
                        :class="{
                            'bg-green-500': t.type === 'success',
                            'bg-red-500': t.type === 'error',
                            'bg-blue-500': t.type === 'info',
                            'bg-yellow-500': t.type === 'warning',
                        }"
                        :style="'width: ' + width + '%'"
                    ></div>
                </div>
            </div>
        </template>
    </div>

    {{-- Global Confirm Modal --}}
    <div
        x-data="{
            show: false,
            message: '',
            callback: null
        }"
        @open-confirm.window="message = $event.detail.message; callback = $event.detail.callback; show = true"
        @keydown.escape.window="show = false"
        x-show="show"
        class="fixed inset-0 z-50"
        style="display: none;"
        role="dialog"
        aria-modal="true"
    >
        {{-- Backdrop --}}
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-black/40"
            @click="show = false"
        ></div>

        {{-- Dialog --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-100"
                @click.stop
            >
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-amber-50 ring-1 ring-amber-100">
                    <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>

                <h3 class="mb-1.5 text-base font-semibold text-gray-900">Konfirmasi Tindakan</h3>
                <p x-text="message" class="mb-6 text-sm leading-relaxed text-gray-500"></p>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        @click="show = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="callback && callback(); show = false"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700"
                    >
                        Ya, Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>

</html>
