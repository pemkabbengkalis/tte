<?php

use App\Enums\JenisDokumen;
use App\Enums\StatusPermohonan;
use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use App\Services\PermohonanService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Permohonan $permohonan;

    public $surat_permohonan;
    public $sk_jabatan;
    public $sk_pangkat;
    public $ktp;

    public function mount(Permohonan $permohonan): void
    {
        $this->authorize('update', $permohonan);

        if ($permohonan->status !== StatusPermohonan::Ditolak) {
            abort(403, 'Permohonan ini tidak dalam status yang dapat diperbaiki.');
        }

        $this->permohonan = $permohonan->load('dokumen');
    }

    protected function fileRule(): array
    {
        return ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:2048'];
    }

    protected function rules(): array
    {
        return [
            'surat_permohonan' => $this->fileRule(),
            'sk_jabatan'       => $this->fileRule(),
            'sk_pangkat'       => $this->fileRule(),
            'ktp'              => $this->fileRule(),
        ];
    }

    protected function messages(): array
    {
        return [
            '*.mimes' => 'Berkas harus berformat PDF, JPG, atau PNG.',
            '*.mimetypes' => 'Tipe berkas tidak valid (PDF, JPG, atau PNG).',
            '*.max' => 'Ukuran berkas maksimal 2MB.',
        ];
    }

    public function kirimUlang(): void
    {
        $this->authorize('update', $this->permohonan);
        abort_unless($this->permohonan->status === StatusPermohonan::Ditolak, 403);

        $this->validate();

        $slots = [
            'surat_permohonan' => $this->surat_permohonan,
            'sk_jabatan'       => $this->sk_jabatan,
            'sk_pangkat'       => $this->sk_pangkat,
            'ktp'              => $this->ktp,
        ];

        $totalDiganti = count(array_filter($slots));

        if ($totalDiganti === 0) {
            $this->addError('surat_permohonan', 'Unggah minimal satu berkas pengganti sebelum mengirim ulang.');
            return;
        }

        foreach ($slots as $jenis => $file) {
            if (!$file) {
                continue;
            }

            $this->validasiNamaFile($file, $jenis);
            $this->validasiSignature($file, $jenis);

            $namaAsli = $file->getClientOriginalName();
            $ukuran = $file->getSize();

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file->getRealPath());
            finfo_close($finfo);

            $allowedMime = [
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/png'       => 'png',
            ];

            if (!isset($allowedMime[$mime])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $jenis => 'Tipe MIME file tidak diizinkan.',
                ]);
            }
            $ekstensi = $allowedMime[$mime]; // lebih aman dari getClientOriginalExtension()

            $versiTertinggi = (int) $this->permohonan->dokumen()->where('jenis_dokumen', $jenis)->max('versi');

            $namaUuid = Str::uuid()->toString() . '.' . $ekstensi;
            $path = $file->storeAs('dokumen', $namaUuid, 'local');

            DokumenPermohonan::create([
                'permohonan_id' => $this->permohonan->id,
                'jenis_dokumen' => $jenis,
                'nama_file' => $namaAsli,
                'path_file' => $path,
                'ukuran_file' => $ukuran,
                'mime_type' => $mime,
                'versi' => $versiTertinggi + 1,
            ]);
        }

        app(PermohonanService::class)->resubmit($this->permohonan);

        session()->flash('ok', "Permohonan {$this->permohonan->nomor_permohonan} berhasil dikirim ulang dan menunggu verifikasi.");
        $this->redirectRoute('pemohon.dashboard', navigate: true);
    }

    private function validasiSignature($file, string $field): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        try {
            $mime = finfo_file(
                $finfo,
                $file->getRealPath()
            );
        } finally {
            finfo_close($finfo);
        }

        $allowed = [
            'application/pdf' => [
                '25504446'
            ],

            'image/jpeg' => [
                'FFD8FF'
            ],

            'image/png' => [
                '89504E47'
            ],
        ];

        if (!isset($allowed[$mime])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Tipe file tidak diizinkan.',
            ]);
        }

        $stream = fopen(
            $file->getRealPath(),
            'rb'
        );

        try {
            $bytes = fread($stream, 8);
        } finally {
            fclose($stream);
        }

        $signature = strtoupper(
            bin2hex($bytes)
        );

        $valid = false;

        foreach ($allowed[$mime] as $magic) {
            if (str_starts_with(
                $signature,
                $magic
            )) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field =>
                    'Isi file tidak sesuai format.',
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
        if (in_array($property, [
            'surat_permohonan',
            'sk_jabatan',
            'sk_pangkat',
            'ktp',
        ])) {
            $this->validateOnly($property);

            $file = $this->$property;
            if ($file) {
                $this->validasiNamaFile($file, $property);
                $this->validasiSignature($file, $property);
            }
        }
    }

    public function with(): array
    {
        $dokumenTerbaru = $this->permohonan->dokumen->sortByDesc('versi')->unique('jenis_dokumen')->keyBy(fn($d) => $d->jenis_dokumen->value);

        return [
            'dokumenTerbaru' => $dokumenTerbaru,
            'jenisDokumenList' => JenisDokumen::cases(),
        ];
    }
};

?>

<div class="mx-auto max-w-3xl space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('pemohon.detail', $permohonan->id) }}" wire:navigate
                class="text-sm font-medium text-gray-500 hover:text-gray-700">&larr; Kembali</a>
            <h1 class="mt-1 text-xl font-semibold text-gray-800">Perbaiki Berkas</h1>
            <p class="text-sm text-gray-500">
                Nomor <span class="font-mono font-medium text-gray-700">{{ $permohonan->nomor_permohonan }}</span>
                &middot; Pengajuan ke-{{ $permohonan->jumlah_pengajuan }}
            </p>
        </div>
        <x-badge-status :status="$permohonan->status" />
    </div>

    {{-- Alasan penolakan --}}
    <div class="rounded-xl border border-red-200 bg-red-50 p-5 ring-1 ring-red-100">
        <p class="text-sm font-semibold text-red-800">Alasan penolakan dari verifikator:</p>
        <p class="mt-1 text-sm leading-relaxed text-red-900">{{ $permohonan->catatan_verifikator }}</p>
    </div>

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-gray-400">Unggah Berkas Pengganti</h2>
        <p class="mb-4 text-sm text-gray-500">Cukup unggah berkas yang ingin diganti. Berkas yang tidak diunggah ulang
            akan tetap menggunakan versi terakhir.</p>

        <div class="space-y-4">
            @foreach ($jenisDokumenList as $j)
                @php
                    $d = $dokumenTerbaru[$j->value] ?? null;
                @endphp

                <x-file-upload-slot :label="$loop->iteration . '. ' . $j->label()" hint="PDF/JPG/PNG, maks 2MB.">
                    <input type="file" wire:model="{{ $j->value }}" accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-600 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-primary-700">
                    <div wire:loading wire:target="{{ $j->value }}" class="mt-1 text-xs text-primary-600">
                        Mengunggah...</div>

                    @if ($d)
                        <p class="mt-1 text-xs text-gray-500">
                            Versi terakhir: <span class="font-medium text-gray-700">{{ $d->nama_file }}</span>
                            &middot; v{{ $d->versi }}
                            &middot; <a href="{{ route('dokumen.lihat', $d->id) }}" target="_blank" rel="noopener"
                                class="text-primary-600 hover:underline">Lihat</a>
                        </p>
                    @else
                        <p class="mt-1 text-xs italic text-red-600">Belum pernah diunggah.</p>
                    @endif

                    @error($j->value)
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </x-file-upload-slot>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('pemohon.detail', $permohonan->id) }}" wire:navigate
            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
            Batal
        </a>
        <button type="button"
            @click="$dispatch('open-confirm', { message: 'Kirim ulang permohonan? Permohonan akan kembali ke antrian verifikasi.', callback: () => $wire.kirimUlang() })"
            wire:loading.attr="disabled"
            class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="kirimUlang">Kirim Ulang</span>
            <span wire:loading wire:target="kirimUlang">Memproses...</span>
        </button>
    </div>
</div>
