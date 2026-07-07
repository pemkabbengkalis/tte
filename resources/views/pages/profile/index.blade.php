<?php

use App\Rules\NoHtmlTags;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $nama_lengkap = '';
    public ?string $nip = null;
    public string $no_hp = '';
    public string $pangkat_gol = '';
    public string $jabatan = '';
    public string $instansi = '';
    public string $unit_kerja = '';

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $u = auth()->user();
        $this->nama_lengkap = $u->nama_lengkap;
        $this->nip = $u->nip;
        $this->no_hp = $u->no_hp ?? '';
        $this->pangkat_gol = $u->pangkat_gol ?? '';
        $this->jabatan = $u->jabatan ?? '';
        $this->instansi = $u->instansi ?? '';
        $this->unit_kerja = $u->unit_kerja ?? '';
    }

    public function simpanProfil(): void
    {
        $u = auth()->user();

        // Normalkan NIP kosong menjadi null agar aturan digits:18 dilewati.
        $this->nip = ($this->nip === null || trim($this->nip) === '') ? null : trim($this->nip);

        $rules = [
            'nama_lengkap' => ['required', 'string', 'min:3', 'max:150', new NoHtmlTags()],
            'nip' => ['nullable', 'digits:18', Rule::unique('users', 'nip')->ignore($u->id)],
            'no_hp' => ['nullable', 'string', 'max:15', new NoHtmlTags()],
        ];

        if ($u->isPemohon()) {
            $rules += [
                'pangkat_gol' => ['nullable', 'string', 'max:50', new NoHtmlTags()],
                'jabatan' => ['nullable', 'string', 'max:100', new NoHtmlTags()],
                'instansi' => ['nullable', 'string', 'max:150', new NoHtmlTags()],
                'unit_kerja' => ['nullable', 'string', 'max:150', new NoHtmlTags()],
            ];
        }

        $data = $this->validate($rules, [
            'nip.digits' => 'NIP harus terdiri dari 18 digit angka.',
            'nip.unique' => 'NIP ini sudah digunakan oleh pengguna lain.',
        ]);

        $u->update([
            'nama_lengkap' => $data['nama_lengkap'],
            'nip' => $data['nip'] ?: null,
            'no_hp' => $data['no_hp'] ?: null,
            'pangkat_gol' => $u->isPemohon() ? $data['pangkat_gol'] ?? null : $u->pangkat_gol,
            'jabatan' => $u->isPemohon() ? $data['jabatan'] ?? null : $u->jabatan,
            'instansi' => $u->isPemohon() ? $data['instansi'] ?? null : $u->instansi,
            'unit_kerja' => $u->isPemohon() ? $data['unit_kerja'] ?? null : $u->unit_kerja,
        ]);

        session()->flash('ok_profil', 'Profil berhasil diperbarui.');
    }

    public function gantiPassword(): void
    {
        $this->validate(
            [
                'current_password' => ['required', 'string'],
                'password' => ['required', 'confirmed', Password::min(8)],
            ],
            [
                'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            ],
        );

        if (!Hash::check($this->current_password, auth()->user()->password)) {
            $this->addError('current_password', 'Kata sandi saat ini salah.');
            return;
        }

        auth()
            ->user()
            ->update([
                'password' => $this->password,
            ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('ok_password', 'Kata sandi berhasil diubah.');
    }
};

?>

<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <h1 class="text-xl font-semibold text-gray-800">Profil Saya</h1>
        <p class="text-sm text-gray-500">Perbarui data pribadi dan kata sandi Anda.</p>
    </div>

    {{-- Identitas (read-only) --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Identitas</h2>
        <p class="mb-4 text-xs text-gray-500">NIK, email, dan peran tidak dapat diubah sendiri. Hubungi administrator jika
            ada kesalahan.</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">NIK</label>
                <input type="text" value="{{ auth()->user()->nik }}" disabled
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-mono text-gray-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input type="text" value="{{ auth()->user()->email }}" disabled
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Peran</label>
                <input type="text" value="{{ auth()->user()->role->label() }}" disabled
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500">
            </div>
        </div>
    </div>

    {{-- Form profil --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Data Diri</h2>

        @if (session('ok_profil'))
            <x-toast :message="session('ok_profil')" />
        @endif

        <form wire:submit="simpanProfil" class="space-y-4">
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
                    <label class="mb-1 block text-sm font-medium text-gray-700">NIP <span
                            class="text-gray-400">(18 digit, opsional)</span></label>
                    <input wire:model="nip" type="text" inputmode="numeric" maxlength="18" autocomplete="off"
                        placeholder="Kosongkan jika belum memiliki NIP"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('nip')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nomor HP</label>
                    <input wire:model="no_hp" type="text" inputmode="numeric"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('no_hp')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if (auth()->user()->isPemohon())
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
                @endif
            </div>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="simpanProfil"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="simpanProfil">Simpan Perubahan</span>
                    <span wire:loading wire:target="simpanProfil">Menyimpan...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Ganti password --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-400">Ubah Kata Sandi</h2>

        @if (session('ok_password'))
            <x-toast :message="session('ok_password')" />
        @endif

        <form wire:submit="gantiPassword" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi Saat Ini</label>
                <input wire:model="current_password" type="password" autocomplete="current-password"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('current_password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi Baru</label>
                    <input wire:model="password" type="password" autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Konfirmasi Kata Sandi Baru</label>
                    <input wire:model="password_confirmation" type="password" autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="gantiPassword"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="gantiPassword">Ubah Kata Sandi</span>
                    <span wire:loading wire:target="gantiPassword">Memproses...</span>
                </button>
            </div>
        </form>
    </div>
</div>
