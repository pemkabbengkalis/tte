<?php
use App\Enums\JenisPermohonan;
use App\Enums\StatusPermohonan;
use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use App\Rules\NoHtmlTags;
use App\Services\PermohonanService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public ?string $nip = null;
    public string $no_hp = '';
    public string $pangkat_gol = '';
    public string $jabatan = '';
    public string $instansi = '';
    public string $unit_kerja = '';

    public string $jenis_permohonan = 'Penerbitan Sertifikat Elektronik';

    public $surat_permohonan;
    public $sk_jabatan;
    public $sk_pangkat;
    public $ktp;

    public ?string $permohonanId = null;
    public array $dokumenTersimpan = [];

    public function mount(?string $permohonan = null): void
    {
        $user = auth()->user();
        $this->nip = $user->nip;
        $this->no_hp = $user->no_hp ?? '';
        $this->pangkat_gol = $user->pangkat_gol ?? '';
        $this->jabatan = $user->jabatan ?? '';
        $this->instansi = $user->instansi ?? '';
        $this->unit_kerja = $user->unit_kerja ?? '';

        if ($permohonan) {
            $pemohon = Permohonan::where('pemohon_id', $user->id)
                ->where('status', StatusPermohonan::Draft)
                ->with('dokumen')
                ->find($permohonan);

            if ($pemohon) {
                $this->permohonanId = $pemohon->id;
                $this->jenis_permohonan = $pemohon->jenis_permohonan?->value ?? 'Penerbitan Sertifikat Elektronik';
                foreach ($pemohon->dokumen as $d) {
                    $this->dokumenTersimpan[$d->jenis_dokumen->value] = $d->nama_file;
                }
            }
        }
    }

    protected function fileRule(): array
    {
        return [
            'nullable', 'file',
            'mimes:pdf,jpg,jpeg,png',
            'mimetypes:application/pdf,image/jpeg,image/png',
            'max:2048',
        ];
    }

    protected function rules(): array
    {
        return [
            'nip'              => ['nullable', 'digits:18', Rule::unique('users', 'nip')->ignore(auth()->id())],
            'no_hp'            => ['nullable', 'string', 'max:15', new NoHtmlTags()],
            'pangkat_gol'      => ['nullable', 'string', 'max:50', new NoHtmlTags()],
            'jabatan'          => ['nullable', 'string', 'max:100', new NoHtmlTags()],
            'instansi'         => ['nullable', 'string', 'max:150', new NoHtmlTags()],
            'unit_kerja'       => ['nullable', 'string', 'max:150', new NoHtmlTags()],
            'jenis_permohonan' => ['required', Rule::in([JenisPermohonan::SertifikatElektronik->value])],
            'surat_permohonan' => $this->fileRule(),
            'sk_jabatan'       => $this->fileRule(),
            'sk_pangkat'       => $this->fileRule(),
            'ktp'              => $this->fileRule(),
        ];
    }

    protected function messages(): array
    {
        return [
            'jenis_permohonan.required' => 'Silakan pilih jenis permohonan.',
            'nip.digits'   => 'NIP harus terdiri dari 18 digit angka.',
            'nip.unique'   => 'NIP ini sudah digunakan oleh pengguna lain.',
            '*.mimes'      => 'Berkas harus berformat PDF, JPG, atau PNG.',
            '*.mimetypes'  => 'Tipe berkas tidak valid (PDF, JPG, atau PNG).',
            '*.max'        => 'Ukuran berkas maksimal 2MB.',
        ];
    }

    private function normalkanNip(): void
    {
        $this->nip = ($this->nip === null || trim($this->nip) === '') ? null : trim($this->nip);
    }

    public function simpanDraft(): void
    {
        $this->normalkanNip();
        $this->validate();
        $this->prosesSimpan(kirim: false);

        session()->flash('ok', 'Draft permohonan berhasil disimpan.');
        $this->redirectRoute('pemohon.dashboard', navigate: true);
    }

    public function kirim(): void
    {
        $this->normalkanNip();
        $this->validate(array_merge($this->rules(), [
            'no_hp'       => ['required', 'string', 'max:15', new NoHtmlTags()],
            'pangkat_gol' => ['required', 'string', 'max:50', new NoHtmlTags()],
            'jabatan'     => ['required', 'string', 'max:100', new NoHtmlTags()],
            'instansi'    => ['required', 'string', 'max:150', new NoHtmlTags()],
            'unit_kerja'  => ['required', 'string', 'max:150', new NoHtmlTags()],
        ]));

        $wajib = [
            'surat_permohonan' => 'Surat Permohonan',
            'sk_jabatan'       => 'SK Jabatan',
            'sk_pangkat'       => 'SK Pangkat',
            'ktp'              => 'KTP',
        ];

        $kurang = false;
        foreach ($wajib as $slot => $label) {
            if (! $this->$slot && ! isset($this->dokumenTersimpan[$slot])) {
                $this->addError($slot, "Dokumen {$label} wajib diunggah.");
                $kurang = true;
            }
        }
        if ($kurang) {
            return;
        }

        $permohonan = $this->prosesSimpan(kirim: true);

        session()->flash('ok', "Permohonan {$permohonan->nomor_permohonan} berhasil dikirim dan menunggu verifikasi.");
        $this->redirectRoute('pemohon.dashboard', navigate: true);
    }

    private function prosesSimpan(bool $kirim): Permohonan
    {
        return DB::transaction(function () use ($kirim) {
            $user = auth()->user();
            $user->update([
                'nip' => $this->nip ?: null,
                'no_hp' => $this->no_hp ?: null,
                'pangkat_gol' => $this->pangkat_gol ?: null,
                'jabatan' => $this->jabatan ?: null,
                'instansi' => $this->instansi ?: null,
                'unit_kerja' => $this->unit_kerja ?: null,
            ]);

            $permohonan = $this->permohonanId
                ? Permohonan::where(
                    'pemohon_id',
                    $user->id
                )->findOrFail(
                    $this->permohonanId
                )
                : new Permohonan();

            $permohonan->pemohon_id=$user->id;
            $permohonan->jenis_permohonan=$this->jenis_permohonan;

            if(!$permohonan->exists){
                $permohonan->status=
                    StatusPermohonan::Draft;

                $permohonan->jumlah_pengajuan=1;
            }

            $permohonan->save();

            $this->simpanDokumen($permohonan);

            if($kirim){
                app(PermohonanService::class)
                    ->submit($permohonan);
            }

            return $permohonan->fresh();
        });
    }

    private function simpanDokumen(Permohonan $permohonan): void
    {
        $slots = [
            'surat_permohonan' => $this->surat_permohonan,
            'sk_jabatan'       => $this->sk_jabatan,
            'sk_pangkat'       => $this->sk_pangkat,
            'ktp'              => $this->ktp,
        ];

        foreach ($slots as $jenis => $file) {
            if (! $file) {
                continue;
            }

            // 1. Validasi nama + signature wajib di sini juga, bukan cuma di updated()
            $this->validasiNamaFile($file, $jenis);
            $this->validasiSignature($file, $jenis);
            
            $namaAsli = $file->getClientOriginalName();
            $ukuran   = $file->getSize();
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file( $finfo, $file->getRealPath() );
            finfo_close($finfo);

            $allowedMime  = [
                'application/pdf'=>'pdf',
                'image/jpeg'=>'jpg',
                'image/png'=>'png'
            ];

            // 2. Double check mime dari Laravel
            if (!isset($allowedMime[$mime])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $jenis => 'Tipe MIME file tidak diizinkan.',
                ]);
            }
            $ekstensi = $allowedMime[$mime]; // lebih aman dari getClientOriginalExtension()

            // 3. Hapus dokumen lama pakai updateOrCreate biar atomik
            $lama = $permohonan->dokumen()->where('jenis_dokumen', $jenis)->lockForUpdate()->first();

            $namaUuid = Str::uuid()->toString() . '.' . $ekstensi;
            $oldPath = $lama?->path_file;
            try{
                $path = $file->storeAs('dokumen', $namaUuid, 'local');

                DokumenPermohonan::updateOrCreate(
                    ['permohonan_id' => $permohonan->id, 'jenis_dokumen' => $jenis],
                    [
                        'nama_file'     => $namaAsli,
                        'path_file'     => $path,
                        'ukuran_file'   => $ukuran,
                        'mime_type'     => $mime,
                        'versi'         => $lama ? $lama->versi + 1 : 1,
                    ]
                );
                if ($oldPath) {
                    Storage::disk('local')
                        ->delete($oldPath);
                }                
            } catch (\Throwable $e) {
                if(isset($path)) {
                    Storage::disk('local')
                        ->delete($path);
                }
                throw $e;
            }

            $this->dokumenTersimpan[$jenis] = $namaAsli;
            $this->reset($jenis); // penting biar TemporaryUploadedFile kehapus
        }
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

        // Cek double extension & null byte
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
                $this->validasiNamaFile($file, $property); // kirim nama field
                $this->validasiSignature($file, $property);
            }
        }
    }
};

?>

<div class="mx-auto max-w-3xl space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Buat Permohonan</h1>
            <p class="text-sm text-gray-500">Lengkapi data diri dan unggah berkas persyaratan.</p>
        </div>
        <a href="{{ route('pemohon.dashboard') }}" wire:navigate class="text-sm font-medium text-gray-500 hover:text-gray-700">&larr; Kembali</a>
    </div>

    {{-- Data diri --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Data Diri</h2>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" value="{{ auth()->user()->nama_lengkap }}" disabled
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">NIP <span
                        class="text-gray-400">(18 digit, opsional)</span></label>
                <input wire:model="nip" type="text" inputmode="numeric" maxlength="18" autocomplete="off"
                    placeholder="Kosongkan jika belum memiliki NIP"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('nip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">NIK</label>
                <input type="text" value="{{ auth()->user()->nik }}" disabled
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input type="text" value="{{ auth()->user()->email }}" disabled
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Nomor HP</label>
                <input wire:model="no_hp" type="text" inputmode="numeric"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('no_hp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Pangkat / Golongan</label>
                <input wire:model="pangkat_gol" type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('pangkat_gol') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Jabatan</label>
                <input wire:model="jabatan" type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('jabatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Instansi</label>
                <input wire:model="instansi" type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('instansi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Unit Kerja</label>
                <input wire:model="unit_kerja" type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('unit_kerja') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Jenis permohonan --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Jenis Permohonan</h2>
        <select wire:model="jenis_permohonan"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            <option value="{{ \App\Enums\JenisPermohonan::SertifikatElektronik->value }}">{{ \App\Enums\JenisPermohonan::SertifikatElektronik->label() }}</option>
        </select>
        <p class="mt-1.5 text-xs text-gray-400">Saat ini permohonan hanya tersedia untuk Penerbitan Sertifikat Elektronik.</p>
        @error('jenis_permohonan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Berkas persyaratan --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Berkas Persyaratan</h2>
            <a href="{{ route('template.download') }}" download
                class="inline-flex items-center gap-1.5 rounded-lg border border-primary-200 px-3 py-1.5 text-xs font-medium text-primary-700 transition hover:bg-primary-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Download Template Surat Permohonan
            </a>
        </div>

        <div class="space-y-4">
            <x-file-upload-slot label="1. Surat Permohonan Penerbitan Sertifikat Elektronik" hint="Unduh template, isi, cetak, tanda tangan, lalu scan. PDF/JPG/PNG, maks 2MB." :required="true">
                <input type="file" wire:model="surat_permohonan" accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-600 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-primary-700">
                <div wire:loading wire:target="surat_permohonan" class="mt-1 text-xs text-primary-600">Mengunggah...</div>
                @isset($dokumenTersimpan['surat_permohonan']) <p class="mt-1 text-xs text-green-600">Tersimpan: {{ $dokumenTersimpan['surat_permohonan'] }}</p> @endisset
                @error('surat_permohonan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-file-upload-slot>

            <x-file-upload-slot label="2. Fotokopi SK Jabatan Terakhir" hint="PDF/JPG/PNG, maks 2MB." :required="true">
                <input type="file" wire:model="sk_jabatan" accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-600 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-primary-700">
                <div wire:loading wire:target="sk_jabatan" class="mt-1 text-xs text-primary-600">Mengunggah...</div>
                @isset($dokumenTersimpan['sk_jabatan']) <p class="mt-1 text-xs text-green-600">Tersimpan: {{ $dokumenTersimpan['sk_jabatan'] }}</p> @endisset
                @error('sk_jabatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-file-upload-slot>

            <x-file-upload-slot label="3. Fotokopi SK Pangkat Terakhir" hint="PDF/JPG/PNG, maks 2MB." :required="true">
                <input type="file" wire:model="sk_pangkat" accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-600 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-primary-700">
                <div wire:loading wire:target="sk_pangkat" class="mt-1 text-xs text-primary-600">Mengunggah...</div>
                @isset($dokumenTersimpan['sk_pangkat']) <p class="mt-1 text-xs text-green-600">Tersimpan: {{ $dokumenTersimpan['sk_pangkat'] }}</p> @endisset
                @error('sk_pangkat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-file-upload-slot>

            <x-file-upload-slot label="4. Fotokopi KTP" hint="PDF/JPG/PNG, maks 2MB." :required="true">
                <input type="file" wire:model="ktp" accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-600 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-primary-700">
                <div wire:loading wire:target="ktp" class="mt-1 text-xs text-primary-600">Mengunggah...</div>
                @isset($dokumenTersimpan['ktp']) <p class="mt-1 text-xs text-green-600">Tersimpan: {{ $dokumenTersimpan['ktp'] }}</p> @endisset
                @error('ktp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-file-upload-slot>
        </div>
    </div>

    {{-- Aksi --}}
    <div class="flex flex-wrap justify-end gap-3">
        <button type="button" wire:click="simpanDraft" wire:loading.attr="disabled"
            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-60">
            Simpan Draft
        </button>
        <button type="button"
            @click="$dispatch('open-confirm', { message: 'Kirim permohonan ini? Setelah dikirim, berkas tidak dapat diubah sampai ada keputusan verifikator.', callback: () => $wire.kirim() })"
            wire:loading.attr="disabled"
            class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
            Kirim Permohonan
        </button>
    </div>
</div>