<?php

use App\Enums\JenisDokumen;
use App\Enums\StatusPermohonan;
use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use App\Services\PermohonanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Permohonan $permohonan;
    public string $alasanTolak = '';
    public bool $modalTolak = false;
    public $hasil_tte;

    public function mount(Permohonan $permohonan): void
    {
        $this->authorize('view', $permohonan);
        $this->permohonan = $permohonan->load(['pemohon', 'dokumen', 'verifikator', 'riwayatVerifikasi.verifikator']);
    }

    protected function fileRule(): array
    {
        return [
            'required', 'file',
            'mimes:pdf,jpg,jpeg,png',
            'mimetypes:application/pdf,image/jpeg,image/png',
            'max:2048',
        ];
    }

    public function bukaModalTolak(): void
    {
        $this->reset('alasanTolak');
        $this->resetErrorBag();
        $this->modalTolak = true;
    }

    public function batalTolak(): void
    {
        $this->modalTolak = false;
        $this->reset('alasanTolak');
        $this->resetErrorBag();
    }

    public function proses(): void
    {
        $this->authorize('verifikasi', $this->permohonan);

        if ($this->permohonan->status !== StatusPermohonan::MenungguVerifikasi) {
            session()->flash('error', 'Permohonan ini tidak dalam status menunggu verifikasi.');
            return;
        }

        try {
            $this->permohonan = app(PermohonanService::class)
                ->proses($this->permohonan, auth()->user())
                ->load(['pemohon', 'dokumen', 'verifikator', 'riwayatVerifikasi.verifikator']);
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Gagal memproses permohonan. Silakan coba lagi.');
            return;
        }

        session()->flash('ok', "Permohonan {$this->permohonan->nomor_permohonan} diverifikasi lengkap dan sedang diproses.");
        $this->redirectRoute('verifikator.dashboard', navigate: true);
    }

    public function terima(): void
    {
        $this->authorize('verifikasi', $this->permohonan);

        if ($this->permohonan->status !== StatusPermohonan::Diproses) {
            session()->flash('error', 'Hanya permohonan yang sedang diproses yang dapat ditandai diterima.');
            return;
        }

        try {
            $this->permohonan = app(PermohonanService::class)
                ->terima($this->permohonan, auth()->user())
                ->load(['pemohon', 'dokumen', 'verifikator', 'riwayatVerifikasi.verifikator']);
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Gagal menandai permohonan sebagai diterima. Silakan coba lagi.');
            return;
        }

        session()->flash('ok', "Permohonan {$this->permohonan->nomor_permohonan} telah selesai dan diterima.");
        $this->redirectRoute('verifikator.dashboard', navigate: true);
    }

    public function tolak(): void
    {
        $this->authorize('verifikasi', $this->permohonan);

        if ($this->permohonan->status !== StatusPermohonan::MenungguVerifikasi) {
            session()->flash('error', 'Penolakan hanya dapat dilakukan saat permohonan menunggu verifikasi.');
            $this->modalTolak = false;
            return;
        }

        $this->validate(
            [
                'alasanTolak' => ['required', 'string', 'min:10', 'max:1000'],
            ],
            [
                'alasanTolak.required' => 'Alasan penolakan wajib diisi.',
                'alasanTolak.min' => 'Alasan penolakan minimal 10 karakter.',
            ],
        );

        try {
            $this->permohonan = app(PermohonanService::class)
                ->tolak($this->permohonan, auth()->user(), $this->alasanTolak)
                ->load(['pemohon', 'dokumen', 'verifikator', 'riwayatVerifikasi.verifikator']);
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Gagal memproses penolakan permohonan. Silakan coba lagi.');
            return;
        }

        session()->flash('ok', "Permohonan {$this->permohonan->nomor_permohonan} telah ditolak. Pemohon akan diberi tahu.");
        $this->redirectRoute('verifikator.dashboard', navigate: true);
    }

    public function kirimTte(): void
    {
        $this->authorize('uploadTte', $this->permohonan);

        if ($this->permohonan->status !== StatusPermohonan::Diterima) {
            session()->flash('error', 'Hasil TTE hanya dapat diunggah untuk permohonan yang sudah diterima.');
            return;
        }

        $this->validate(['hasil_tte' => $this->fileRule()], [
            'hasil_tte.required'  => 'Berkas hasil TTE wajib diunggah.',
            'hasil_tte.mimes'     => 'Berkas harus berformat PDF, JPG, atau PNG.',
            'hasil_tte.mimetypes' => 'Tipe berkas tidak valid (PDF, JPG, atau PNG).',
            'hasil_tte.max'       => 'Ukuran berkas maksimal 2MB.',
        ]);

        $file = $this->hasil_tte;
        $this->validasiNamaFile($file, 'hasil_tte');
        $this->validasiSignature($file, 'hasil_tte');

        $namaAsli = $file->getClientOriginalName();
        $ukuran   = $file->getSize();

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        $allowedMime = [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
        ];

        if (! isset($allowedMime[$mime])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hasil_tte' => 'Tipe MIME file tidak diizinkan.',
            ]);
        }
        $ekstensi = $allowedMime[$mime];

        try {
            DB::transaction(function () use ($file, $namaAsli, $ukuran, $mime, $ekstensi) {
                $lama = $this->permohonan->dokumen()->where('jenis_dokumen', 'hasil_tte')->lockForUpdate()->first();

                $namaUuid = Str::uuid()->toString() . '.' . $ekstensi;
                $oldPath  = $lama?->path_file;
                $path     = $file->storeAs('dokumen', $namaUuid, 'local');

                try {
                    DokumenPermohonan::updateOrCreate(
                        ['permohonan_id' => $this->permohonan->id, 'jenis_dokumen' => 'hasil_tte'],
                        [
                            'nama_file'   => $namaAsli,
                            'path_file'   => $path,
                            'ukuran_file' => $ukuran,
                            'mime_type'   => $mime,
                            'versi'       => $lama ? $lama->versi + 1 : 1,
                        ]
                    );
                    if ($oldPath) {
                        Storage::disk('local')->delete($oldPath);
                    }
                } catch (\Throwable $e) {
                    Storage::disk('local')->delete($path);
                    throw $e;
                }

                app(PermohonanService::class)->selesaikan($this->permohonan, auth()->user());
            });
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Gagal mengirim hasil TTE. Silakan coba lagi.');
            return;
        }

        $this->reset('hasil_tte');
        session()->flash('ok', "Hasil TTE untuk permohonan {$this->permohonan->nomor_permohonan} berhasil dikirim. Permohonan selesai.");
        $this->redirectRoute('verifikator.dashboard', navigate: true);
    }

    private function validasiSignature($file, string $field): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        try {
            $mime = finfo_file($finfo, $file->getRealPath());
        } finally {
            finfo_close($finfo);
        }

        $allowed = [
            'application/pdf' => ['25504446'],
            'image/jpeg'      => ['FFD8FF'],
            'image/png'       => ['89504E47'],
        ];

        if (! isset($allowed[$mime])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Tipe file tidak diizinkan.',
            ]);
        }

        $stream = fopen($file->getRealPath(), 'rb');
        try {
            $bytes = fread($stream, 8);
        } finally {
            fclose($stream);
        }

        $signature = strtoupper(bin2hex($bytes));

        $valid = false;
        foreach ($allowed[$mime] as $magic) {
            if (str_starts_with($signature, $magic)) {
                $valid = true;
                break;
            }
        }

        if (! $valid) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Isi file tidak sesuai format.',
            ]);
        }
    }

    private function validasiNamaFile($file, string $field): void
    {
        $nama = strtolower($file->getClientOriginalName());

        if (preg_match('/\.(php|phtml|phar|cgi|pl|asp|aspx|jsp|exe|sh|bat)(\.|$)/i', $nama) || str_contains($nama, "\0")) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Nama file mengandung ekstensi yang tidak diizinkan.',
            ]);
        }
    }

    public function updated($property): void
    {
        if ($property === 'hasil_tte' && $this->hasil_tte) {
            $this->validateOnly($property, ['hasil_tte' => $this->fileRule()]);
            $this->validasiNamaFile($this->hasil_tte, $property);
            $this->validasiSignature($this->hasil_tte, $property);
        }
    }

    public function with(): array
    {
        // Susun dokumen terbaru per jenis (versi tertinggi)
        $dokumenTerbaru = $this->permohonan->dokumen->sortByDesc('versi')->unique('jenis_dokumen')->keyBy(fn($d) => $d->jenis_dokumen->value);

        return [
            'dokumenTerbaru' => $dokumenTerbaru,
            'jenisDokumenList' => JenisDokumen::persyaratan(),
            'dapatDiproses' => $this->permohonan->status === StatusPermohonan::MenungguVerifikasi,
            'dapatDiselesaikan' => $this->permohonan->status === StatusPermohonan::Diproses,
            'dapatUploadTte' => $this->permohonan->status === StatusPermohonan::Diterima,
        ];
    }
};

?>

<div class="space-y-5">
    @if (session('error'))
        <x-toast :message="session('error')" type="error" />
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('verifikator.dashboard') }}" wire:navigate
                class="text-sm font-medium text-gray-500 hover:text-gray-700">&larr; Kembali ke daftar</a>
            <h1 class="mt-1 text-xl font-semibold text-gray-800">Detail Permohonan</h1>
            <p class="text-sm text-gray-500">
                Nomor <span class="font-mono font-medium text-gray-700">{{ $permohonan->nomor_permohonan }}</span>
                &middot; Pengajuan ke-{{ $permohonan->jumlah_pengajuan }}
            </p>
        </div>
        <x-badge-status :status="$permohonan->status" />
    </div>

    <div class="grid gap-5 lg:grid-cols-3">

        <div class="space-y-5 lg:col-span-1">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Data Pemohon</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400">Nama Lengkap</dt>
                        <dd class="font-medium text-gray-800">{{ $permohonan->pemohon->nama_lengkap }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">NIP</dt>
                        <dd class="font-mono text-gray-700">{{ $permohonan->pemohon->nip ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">NIK</dt>
                        <dd class="font-mono text-gray-700">{{ $permohonan->pemohon->nik }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Email</dt>
                        <dd class="break-all text-gray-700">{{ $permohonan->pemohon->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Nomor HP</dt>
                        <dd class="text-gray-700">{{ $permohonan->pemohon->no_hp ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Pangkat / Golongan</dt>
                        <dd class="text-gray-700">{{ $permohonan->pemohon->pangkat_gol ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Jabatan</dt>
                        <dd class="text-gray-700">{{ $permohonan->pemohon->jabatan ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Instansi</dt>
                        <dd class="text-gray-700">{{ $permohonan->pemohon->instansi ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Unit Kerja</dt>
                        <dd class="text-gray-700">{{ $permohonan->pemohon->unit_kerja ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

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
                            <dt class="text-xs text-gray-400">Tanggal Verifikasi</dt>
                            <dd class="text-gray-700">
                                {{ $permohonan->tanggal_verifikasi->translatedFormat('d F Y, H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">Verifikator</dt>
                            <dd class="text-gray-700">{{ $permohonan->verifikator?->nama_lengkap ?? '-' }}</dd>
                        </div>
                    @endif
                    @if ($permohonan->catatan_verifikator)
                        <div>
                            <dt class="text-xs text-gray-400">Catatan / Alasan</dt>
                            <dd class="rounded-md bg-red-50 p-2 text-sm text-red-800 ring-1 ring-red-100">
                                {{ $permohonan->catatan_verifikator }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Berkas Persyaratan</h2>

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

            @if ($dapatDiproses)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Keputusan</h2>
                    <p class="mb-4 text-sm text-gray-600">Periksa kelengkapan dan keabsahan berkas. Jika lengkap dan
                        sesuai, lanjutkan untuk diproses; jika tidak, tolak permohonan.</p>
                    <div class="flex flex-wrap gap-3">
                        <button
                            @click="$dispatch('open-confirm', { message: 'Berkas lengkap dan sesuai? Permohonan akan diproses dan pemohon diberi tahu.', callback: () => $wire.proses() })"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Proses Permohonan
                        </button>
                        <button wire:click="bukaModalTolak"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Tolak
                        </button>
                    </div>
                </div>
            @endif

            @if ($dapatDiselesaikan)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Penyelesaian</h2>
                    <p class="mb-4 text-sm text-gray-600">Permohonan ini sedang diproses. Jika sertifikat elektronik
                        telah selesai diterbitkan, tandai permohonan sebagai diterima.</p>
                    <button
                        @click="$dispatch('open-confirm', { message: 'Tandai permohonan ini sebagai diterima (selesai)? Pemohon akan diberi tahu.', callback: () => $wire.terima() })"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 disabled:opacity-60">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Tandai Diterima
                    </button>
                </div>
            @endif

            @if ($dapatUploadTte)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Kirim Hasil TTE</h2>
                    <p class="mb-4 text-sm text-gray-600">Permohonan ini telah diterima. Unggah berkas hasil tanda
                        tangan elektronik (TTE) untuk dikirim ke pemohon. Setelah dikirim, permohonan akan ditandai
                        selesai.</p>

                    <x-file-upload-slot label="Hasil TTE" hint="PDF/JPG/PNG, maks 2MB." :required="true">
                        <input type="file" wire:model="hasil_tte" accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-600 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-primary-700">
                        <div wire:loading wire:target="hasil_tte" class="mt-1 text-xs text-primary-600">
                            Mengunggah...</div>
                        @error('hasil_tte')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </x-file-upload-slot>

                    <div class="mt-4 flex justify-end">
                        <button type="button"
                            @click="$dispatch('open-confirm', { message: 'Kirim hasil TTE ini ke pemohon? Permohonan akan ditandai selesai.', callback: () => $wire.kirimTte() })"
                            wire:loading.attr="disabled"
                            {{-- rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary-700 --}}
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="kirimTte">Kirim Hasil TTE</span>
                            <span wire:loading wire:target="kirimTte">Mengirim...</span>
                        </button>
                    </div>
                </div>
            @endif

            @if ($permohonan->riwayatVerifikasi->isNotEmpty())
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Riwayat Verifikasi</h2>
                    <ol class="space-y-3">
                        @foreach ($permohonan->riwayatVerifikasi->sortByDesc('created_at') as $r)
                            @php
                                $aksiStyle = match ($r->aksi) {
                                    'diterima' => ['bg' => 'bg-green-100 text-green-700', 'kata' => 'menerima'],
                                    'diproses' => ['bg' => 'bg-indigo-100 text-indigo-700', 'kata' => 'memproses'],
                                    'selesai' => ['bg' => 'bg-blue-100 text-blue-700', 'kata' => 'mengirim hasil TTE untuk'],
                                    default => ['bg' => 'bg-red-100 text-red-700', 'kata' => 'menolak'],
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
                                        <span class="font-medium">{{ $r->verifikator->nama_lengkap }}</span>
                                        {{ $aksiStyle['kata'] }} permohonan.
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

    @if ($modalTolak)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            wire:keydown.escape.window="batalTolak">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl" @click.outside="$wire.batalTolak()">
                <h3 class="text-lg font-semibold text-gray-800">Tolak Permohonan</h3>
                <p class="mt-1 text-sm text-gray-500">Berikan alasan yang jelas agar pemohon dapat memperbaiki
                    berkasnya.</p>

                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Alasan Penolakan <span
                            class="text-xs text-gray-400">(min. 10 karakter)</span></label>
                    <textarea wire:model="alasanTolak" rows="5"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                        placeholder="Contoh: Berkas KTP buram dan tidak dapat dibaca. Mohon unggah ulang dengan kualitas yang lebih baik."></textarea>
                    @error('alasanTolak')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button wire:click="batalTolak" type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                        Batal
                    </button>
                    <button wire:click="tolak" type="button" wire:loading.attr="disabled" wire:target="tolak"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="tolak">Konfirmasi Tolak</span>
                        <span wire:loading wire:target="tolak">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
