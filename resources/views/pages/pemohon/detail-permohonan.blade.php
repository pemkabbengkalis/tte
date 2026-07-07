<?php

use App\Enums\JenisDokumen;
use App\Enums\StatusPermohonan;
use App\Models\Permohonan;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    public Permohonan $permohonan;

    public function mount(Permohonan $permohonan): void
    {
        $this->authorize('view', $permohonan);
        $this->permohonan = $permohonan->load(['dokumen', 'verifikator', 'riwayatVerifikasi.verifikator']);
    }

    public function with(): array
    {
        $dokumenTerbaru = $this->permohonan->dokumen->sortByDesc('versi')->unique('jenis_dokumen')->keyBy(fn($d) => $d->jenis_dokumen->value);

        return [
            'dokumenTerbaru' => $dokumenTerbaru,
            'jenisDokumenList' => JenisDokumen::cases(),
            'bisaPerbaiki' => $this->permohonan->status === StatusPermohonan::Ditolak,
            'bisaLanjutDraft' => $this->permohonan->status === StatusPermohonan::Draft,
        ];
    }
};

?>

<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('pemohon.dashboard') }}" wire:navigate
                class="text-sm font-medium text-gray-500 hover:text-gray-700">&larr; Kembali ke dashboard</a>
            <h1 class="mt-1 text-xl font-semibold text-gray-800">Detail Permohonan</h1>
            <p class="text-sm text-gray-500">
                Nomor <span class="font-mono font-medium text-gray-700">{{ $permohonan->nomor_permohonan ?? '—' }}</span>
                &middot; Pengajuan ke-{{ $permohonan->jumlah_pengajuan }}
            </p>
        </div>
        <x-badge-status :status="$permohonan->status" />
    </div>

    {{-- Alasan penolakan (prioritas tampilan) --}}
    @if ($permohonan->status === StatusPermohonan::Ditolak && $permohonan->catatan_verifikator)
        <div class="rounded-xl border border-red-200 bg-red-50 p-5 ring-1 ring-red-100">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-red-800">Permohonan Anda ditolak.</p>
                    <p class="mt-1 text-sm leading-relaxed text-red-900">{{ $permohonan->catatan_verifikator }}</p>
                    <p class="mt-2 text-xs text-red-700">
                        Oleh: {{ $permohonan->verifikator?->nama_lengkap ?? '-' }}
                        &middot; {{ $permohonan->tanggal_verifikasi?->translatedFormat('d F Y, H:i') }}
                    </p>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <a href="{{ route('pemohon.perbaiki', $permohonan->id) }}" wire:navigate
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                    Perbaiki Berkas
                </a>
            </div>
        </div>
    @endif

    {{-- Sedang diproses --}}
    @if ($permohonan->status === StatusPermohonan::Diproses)
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5 ring-1 ring-indigo-100">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-indigo-800">Permohonan Anda sedang diproses.</p>
                    <p class="mt-1 text-sm text-indigo-900">Berkas telah diverifikasi lengkap. Mohon menunggu proses
                        penerbitan sertifikat elektronik selesai.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Konfirmasi diterima --}}
    @if ($permohonan->status === StatusPermohonan::Diterima)
        <div class="rounded-xl border border-green-200 bg-green-50 p-5 ring-1 ring-green-100">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-green-800">Permohonan Anda diterima.</p>
                    <p class="mt-1 text-sm text-green-900">Silakan menunggu proses penerbitan sertifikat elektronik.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">

        {{-- Kolom kiri --}}
        <div class="space-y-5 lg:col-span-1">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Detail Permohonan</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400">Jenis Permohonan</dt>
                        <dd class="font-medium text-gray-800">{{ $permohonan->jenis_permohonan->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Tanggal Pengajuan</dt>
                        <dd class="text-gray-700">
                            {{ $permohonan->tanggal_pengajuan?->translatedFormat('d F Y, H:i') ?? '-' }}</dd>
                    </div>
                    @if ($permohonan->tanggal_verifikasi)
                        <div>
                            <dt class="text-xs text-gray-400">Tanggal Verifikasi Terakhir</dt>
                            <dd class="text-gray-700">
                                {{ $permohonan->tanggal_verifikasi->translatedFormat('d F Y, H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Kolom kanan --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Berkas yang Diunggah</h2>

                <ul class="divide-y divide-gray-100">
                    @foreach ($jenisDokumenList as $j)
                        @php
                            $d = $dokumenTerbaru[$j->value] ?? null;
                        @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ $j->label() }}</p>
                                @if ($d)
                                    <p class="truncate text-xs text-gray-500">{{ $d->nama_file }} &middot;
                                        {{ $d->ukuranTerbaca() }} &middot; v{{ $d->versi }}</p>
                                @else
                                    <p class="text-xs italic text-red-600">Belum diunggah</p>
                                @endif
                            </div>
                            @if ($d)
                                <div class="flex shrink-0 gap-2">
                                    <a href="{{ route('dokumen.lihat', $d->id) }}" target="_blank" rel="noopener"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                        Lihat
                                    </a>
                                    <a href="{{ route('dokumen.unduh', $d->id) }}"
                                        class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary-700">
                                        Unduh
                                    </a>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Aksi --}}
            @if ($bisaLanjutDraft)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-3 text-sm text-gray-600">Permohonan ini masih berstatus draft.</p>
                    <a href="{{ route('pemohon.buat', $permohonan->id) }}" wire:navigate
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                        Lanjutkan Draft
                    </a>
                </div>
            @endif

            {{-- Riwayat verifikasi --}}
            @if ($permohonan->riwayatVerifikasi->isNotEmpty())
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Riwayat Verifikasi</h2>
                    <ol class="space-y-3">
                        @foreach ($permohonan->riwayatVerifikasi->sortByDesc('created_at') as $r)
                            @php
                                $aksiStyle = match ($r->aksi) {
                                    'diterima' => ['bg' => 'bg-green-100 text-green-700', 'kata' => 'diterima'],
                                    'diproses' => ['bg' => 'bg-indigo-100 text-indigo-700', 'kata' => 'diproses'],
                                    default => ['bg' => 'bg-red-100 text-red-700', 'kata' => 'ditolak'],
                                };
                            @endphp
                            <li class="flex gap-3">
                                <div
                                    class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $aksiStyle['bg'] }}">
                                    @if ($r->aksi === 'ditolak')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800">
                                        Permohonan {{ $aksiStyle['kata'] }}
                                        <span class="text-gray-400">oleh {{ $r->verifikator->nama_lengkap }}</span>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $r->created_at->translatedFormat('d F Y, H:i') }}</p>
                                    @if ($r->catatan)
                                        <p class="mt-1 rounded-md bg-gray-50 p-2 text-xs text-gray-700">
                                            {{ $r->catatan }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </div>
</div>
