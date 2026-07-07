<?php

use App\Models\TemplateDokumen;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public string $nama_template = 'Template Surat Permohonan Sertifikat Elektronik';
    public string $deskripsi = '';
    public string $versi = '1.0';
    public $file;

    protected function rules(): array
    {
        return [
            'nama_template' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'versi' => ['required', 'string', 'max:10'],
            'file' => ['required', 'file', 'mimes:doc,docx', 'max:5120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'file.mimes' => 'Template harus berformat .doc atau .docx.',
            'file.max' => 'Ukuran template maksimal 5MB.',
        ];
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $this->validate();

        // Hardening tambahan
        $this->validasiNamaFile(
            $this->file,
            'file'
        );

        $this->validasiSignature(
            $this->file,
            'file'
        );

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        try {
            $mime = finfo_file(
                $finfo,
                $this->file->getRealPath()
            );
        } finally {
            finfo_close($finfo);
        }

        $allowedMime = [
            'application/msword' =>
                'doc',

            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                =>
                'docx',
        ];

        if (!isset($allowedMime[$mime])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'file' =>
                    'Format file tidak valid.',
            ]);
        }

        $ekstensi = $allowedMime[$mime];

        $namaFile =
            Str::uuid()
            . '.'
            . $ekstensi;

        DB::transaction(function () use (
            $data,
            $namaFile
        ) {

            $path = null;

            try {

                $path =
                    $this->file->storeAs(
                        'templates',
                        $namaFile,
                        'local'
                    );

                TemplateDokumen::where(
                    'is_active',
                    true
                )
                ->lockForUpdate()
                ->update([
                    'is_active' => false
                ]);

                TemplateDokumen::create([
                    'nama_template' =>
                        $data['nama_template'],

                    'deskripsi' =>
                        $data['deskripsi']
                        ?: null,

                    'nama_file' =>
                        $this->file
                            ->getClientOriginalName(),

                    'path_file' =>
                        $path,

                    'versi' =>
                        $data['versi'],

                    'is_active' =>
                        true,

                    'uploaded_by' =>
                        auth()->id(),
                ]);

            } catch (\Throwable $e) {

                if ($path) {
                    Storage::disk('local')
                        ->delete($path);
                }

                throw $e;
            }
        });

        $this->reset([
            'deskripsi',
            'file'
        ]);

        $this->versi='1.0';

        session()->flash(
            'ok',
            'Template berhasil diunggah dan diaktifkan.'
        );
    }

    public function aktifkan(string $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        try {
            $template = TemplateDokumen::findOrFail($id);

            TemplateDokumen::where('is_active', true)->update(['is_active' => false]);
            $template->update(['is_active' => true]);

            session()->flash('ok', 'Template "' . $template->nama_template . '" berhasil diaktifkan.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal mengaktifkan template. Silakan coba lagi.');
        }
    }

    public function hapus(string $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $template = TemplateDokumen::findOrFail($id);
        Storage::disk('local')->delete($template->path_file);
        $template->forceDelete();

        session()->flash('ok', 'Template berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'templates' => TemplateDokumen::orderByDesc('created_at')->get(),
        ];
    }

    public function updatedFile(): void
    {
        $this->validateOnly('file');

        if ($this->file) {
            $this->validasiNamaFile(
                $this->file,
                'file'
            );

            $this->validasiSignature(
                $this->file,
                'file'
            );
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
            'application/msword' => [
                'D0CF11E0'
            ],

            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
                '504B0304'
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
};

?>

<div class="space-y-5">
    <div>
        <h1 class="text-xl font-semibold text-gray-800">Kelola Template Surat</h1>
        <p class="text-sm text-gray-500">Unggah dan kelola template surat permohonan yang dapat diunduh pemohon.</p>
    </div>

    @if (session('ok'))
        <x-toast :message="session('ok')" />
    @endif

    @if (session('error'))
        <x-toast :message="session('error')" type="error" />
    @endif

    {{-- Form unggah --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Unggah Template Baru</h2>
        <form wire:submit="simpan" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama Template</label>
                    <input wire:model="nama_template" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('nama_template')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Versi</label>
                    <input wire:model="versi" type="text" placeholder="1.0"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('versi')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Deskripsi <span
                        class="text-gray-400">(opsional)</span></label>
                <textarea wire:model="deskripsi" rows="2"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"></textarea>
                @error('deskripsi')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-file-upload-slot label="Berkas Template" hint="Format .doc atau .docx, maks 5MB." :required="true">
                <input type="file" wire:model="file" accept=".doc,.docx"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary-600 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:file:bg-primary-700">
                <div wire:loading wire:target="file" class="mt-1 text-xs text-primary-600">Mengunggah berkas...</div>
                @error('file')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </x-file-upload-slot>

            <button type="submit" wire:loading.attr="disabled" wire:target="simpan,file"
                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="simpan">Unggah & Aktifkan</span>
                <span wire:loading wire:target="simpan">Menyimpan...</span>
            </button>
        </form>
    </div>

    {{-- Daftar template --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 p-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Daftar Template</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama / Berkas</th>
                        <th class="px-4 py-3 font-medium">Versi</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($templates as $t)
                        <tr wire:key="{{ $t->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $t->nama_template }}</div>
                                <div class="text-xs text-gray-400">{{ $t->nama_file }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $t->versi }}</td>
                            <td class="px-4 py-3">
                                @if ($t->is_active)
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-200">Aktif</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    @unless ($t->is_active)
                                        <button type="button"
                                            @click="$dispatch('open-confirm', { message: 'Aktifkan template \'{{ addslashes($t->nama_template) }}\'? Template yang aktif sebelumnya akan dinonaktifkan.', callback: () => $wire.aktifkan('{{ $t->id }}') })"
                                            class="rounded-lg border border-primary-200 px-3 py-1.5 text-xs font-medium text-primary-600 transition hover:bg-primary-50">Aktifkan</button>
                                    @endunless
                                    <button
                                        @click="$dispatch('open-confirm', { message: 'Hapus template ini secara permanen?', callback: () => $wire.hapus('{{ $t->id }}') })"
                                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400">Belum ada template
                                yang diunggah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
