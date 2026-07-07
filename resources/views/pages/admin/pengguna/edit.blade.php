<?php

use App\Enums\RoleUser;
use App\Models\User;
use App\Rules\NoHtmlTags;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    public User $user;

    public string $nama_lengkap = '';
    public string $nip = '';
    public string $nik = '';
    public string $email = '';
    public string $no_hp = '';
    public string $pangkat_gol = '';
    public string $jabatan = '';
    public string $instansi = '';
    public string $unit_kerja = '';
    public string $role = '';

    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;
        $this->nama_lengkap = $user->nama_lengkap;
        $this->nip = $user->nip ?? '';
        $this->nik = $user->nik;
        $this->email = $user->email;
        $this->no_hp = $user->no_hp ?? '';
        $this->pangkat_gol = $user->pangkat_gol ?? '';
        $this->jabatan = $user->jabatan ?? '';
        $this->instansi = $user->instansi ?? '';
        $this->unit_kerja = $user->unit_kerja ?? '';
        $this->role = $user->role->value;
    }

    public function simpan(): void
    {
        $this->authorize('update', $this->user);

        $data = $this->validate(
            [
                'nama_lengkap' => ['required', 'string', 'min:3', 'max:150', new NoHtmlTags()],
                'nip'          => ['nullable', 'digits:18', Rule::unique('users', 'nip')->ignore($this->user->id)],
                'nik'          => ['required', 'digits:16', Rule::unique('users', 'nik')->ignore($this->user->id)],
                'email'        => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->user->id)],
                'no_hp'        => ['nullable', 'string', 'max:15', new NoHtmlTags()],
                'pangkat_gol'  => ['nullable', 'string', 'max:50', new NoHtmlTags()],
                'jabatan'      => ['nullable', 'string', 'max:100', new NoHtmlTags()],
                'instansi'     => ['nullable', 'string', 'max:150', new NoHtmlTags()],
                'unit_kerja'   => ['nullable', 'string', 'max:150', new NoHtmlTags()],
                'role'         => ['required', Rule::in(['pemohon', 'verifikator'])],
            ],
            [
                'nip.digits' => 'NIP harus terdiri dari 18 digit angka.',
                'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            ],
        );

        $this->user->update([
            'nama_lengkap' => $data['nama_lengkap'],
            'nip'          => $data['nip'] ?: null,
            'nik'          => $data['nik'],
            'email'        => $data['email'],
            'no_hp' => $data['no_hp'] ?: null,
            'pangkat_gol' => $data['pangkat_gol'] ?: null,
            'jabatan' => $data['jabatan'] ?: null,
            'instansi' => $data['instansi'] ?: null,
            'unit_kerja' => $data['unit_kerja'] ?: null,
            'role' => $data['role'],
        ]);

        session()->flash('ok', "Data \"{$this->user->nama_lengkap}\" berhasil diperbarui.");
        $this->redirectRoute('admin.pengguna.index', navigate: true);
    }
};

?>

<div class="mx-auto max-w-3xl space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.pengguna.index') }}" wire:navigate
                class="text-sm font-medium text-gray-500 hover:text-gray-700">&larr; Kembali</a>
            <h1 class="mt-1 text-xl font-semibold text-gray-800">Edit Pengguna</h1>
            <p class="text-sm text-gray-500">Edit data inti akun. Ubah peran hanya untuk pengguna yang belum punya
                riwayat.</p>
        </div>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-100">
        <p class="font-medium">Catatan keamanan</p>
        <p class="mt-1">Halaman ini hanya tersedia karena pengguna <span class="font-medium">belum memiliki
                riwayat</span> di sistem. Setelah pengguna pertama kali membuat permohonan atau memverifikasi, data inti
            akan terkunci permanen demi menjaga integritas audit trail.</p>
    </div>

    <form wire:submit="simpan" class="space-y-5">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Data Identitas</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input wire:model="nama_lengkap" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('nama_lengkap')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">NIP (18 digit)</label>
                    <input wire:model="nip" type="text" inputmode="numeric" maxlength="18"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('nip')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">NIK (16 digit)</label>
                    <input wire:model="nik" type="text" inputmode="numeric" maxlength="16"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('nik')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                    <input wire:model="email" type="email"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Peran</label>
                    <select wire:model="role" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="pemohon">Pemohon</option>
                        <option value="verifikator">Verifikator</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Data Tambahan</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nomor HP</label>
                    <input wire:model="no_hp" type="text" inputmode="numeric"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('no_hp')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Pangkat / Golongan</label>
                    <input wire:model="pangkat_gol" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('pangkat_gol')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Jabatan</label>
                    <input wire:model="jabatan" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('jabatan')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Instansi</label>
                    <input wire:model="instansi" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('instansi')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Unit Kerja</label>
                    <input wire:model="unit_kerja" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('unit_kerja')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.pengguna.index') }}" wire:navigate
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled" wire:target="simpan"
                class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="simpan">Simpan Perubahan</span>
                <span wire:loading wire:target="simpan">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
