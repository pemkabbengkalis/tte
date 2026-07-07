<?php

use App\Enums\RoleUser;
use App\Models\User;
use App\Rules\NoHtmlTags;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $nama_lengkap = '';
    public ?string $nip = null;
    public string $nik = '';
    public string $email = '';
    public string $instansi = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'min:3', 'max:150', new NoHtmlTags()],
            'nip'          => ['nullable', 'digits:18', 'unique:users,nip'],
            'nik'          => ['required', 'digits:16', 'unique:users,nik'],
            'email'        => ['required', 'email', 'max:150', 'unique:users,email'],
            'instansi'     => ['nullable', 'string', 'max:150', new NoHtmlTags()],
            'password'     => ['required', 'confirmed', 'min:8'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nip.digits'           => 'NIP harus terdiri dari 18 digit angka.',
            'nip.unique'           => 'NIP ini sudah terdaftar.',
            'nik.digits'           => 'NIK harus terdiri dari 16 digit angka.',
            'email.unique'         => 'Email ini sudah terdaftar.',
            'password.min'         => 'Kata sandi minimal 8 karakter.',
            'password.confirmed'   => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }

    public function register(): void
    {
        // Normalkan input kosong menjadi null agar aturan digits:18 dilewati
        // ketika pemohon belum memiliki NIP.
        $this->nip = ($this->nip === null || trim($this->nip) === '') ? null : trim($this->nip);

        $data = $this->validate();

        User::create([
            'nama_lengkap' => $data['nama_lengkap'],
            'nip'          => $data['nip'] ?: null,
            'nik'          => $data['nik'],
            'email'        => $data['email'],
            'instansi'     => $data['instansi'] ?: null,
            'password'     => $data['password'],
            'role'         => RoleUser::Pemohon,
        ]);

        session()->flash('status', 'registered');
        $this->redirect(route('login'), navigate: true);
    }
};

?>

<div>
    <div class="mb-6 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Daftar Akun Pemohon</h2>
        <p class="mt-1 text-sm text-gray-500">Isi data diri Anda untuk mendaftar.</p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label for="nama_lengkap" class="mb-1 block text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input wire:model="nama_lengkap" id="nama_lengkap" type="text" autocomplete="name"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            @error('nama_lengkap')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="nip" class="mb-1 block text-sm font-medium text-gray-700">NIP <span
                    class="text-gray-400">(18 digit, opsional)</span></label>
            <input wire:model="nip" id="nip" type="text" inputmode="numeric" maxlength="18" autocomplete="off"
                placeholder="Kosongkan jika belum memiliki NIP"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            @error('nip')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="nik" class="mb-1 block text-sm font-medium text-gray-700">NIK <span
                    class="text-gray-400">(16 digit)</span></label>
            <input wire:model="nik" id="nik" type="text" inputmode="numeric" maxlength="16" autocomplete="off"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            @error('nik')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="instansi" class="mb-1 block text-sm font-medium text-gray-700">Perangkat Daerah / Instansi
                <span class="text-gray-400">(opsional)</span></label>
            <input wire:model="instansi" id="instansi" type="text" autocomplete="organization"
                placeholder="Contoh: Dinas Pendidikan dan Kebudayaan"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            @error('instansi')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi</label>
            <div class="relative">
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'"
                    autocomplete="new-password"
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

        <div x-data="{ show: false }">
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Konfirmasi Kata
                Sandi</label>
            <div class="relative">
                <input wire:model="password_confirmation" id="password_confirmation"
                    :type="show ? 'text' : 'password'" autocomplete="new-password"
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
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="register"
            class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60">
            <span wire:loading.remove wire:target="register">Daftar</span>
            <span wire:loading wire:target="register">Memproses...</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-gray-500">
        Sudah punya akun?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-primary-600 hover:text-primary-700">Masuk
            di sini</a>
    </p>
</div>
