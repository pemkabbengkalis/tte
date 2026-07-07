<?php

use App\Enums\RoleUser;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'role', except: 'semua')]
    public string $filterRole = 'semua';

    #[Url(as: 'status', except: 'semua')]
    public string $filterStatus = 'semua';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingFilterRole(): void
    {
        $this->resetPage();
    }
    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function aktifkan(string $id): void
    {
        $u = User::findOrFail($id);
        $this->authorize('toggleAktivasi', $u);

        $u->aktifkan();
        session()->flash('ok', "Akun \"{$u->nama_lengkap}\" berhasil diaktifkan.");
    }

    public function nonaktifkan(string $id): void
    {
        $u = User::findOrFail($id);
        $this->authorize('toggleAktivasi', $u);

        $u->nonaktifkan();
        session()->flash('ok', "Akun \"{$u->nama_lengkap}\" berhasil dinonaktifkan.");
    }

    public function hapus(string $id): void
    {
        $u = User::findOrFail($id);
        $this->authorize('delete', $u);

        $nama = $u->nama_lengkap;
        $u->delete();

        session()->flash('ok', "Akun \"{$nama}\" berhasil dihapus.");
    }

    public function with(): array
    {
        $query = User::query()->where('role', '!=', RoleUser::Admin->value);

        if ($this->filterRole !== 'semua') {
            $query->where('role', $this->filterRole);
        }

        if ($this->filterStatus === 'aktif') {
            $query->whereNotNull('email_verified_at');
        } elseif ($this->filterStatus === 'belum') {
            $query->whereNull('email_verified_at');
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nama_lengkap', 'like', $term)->orWhere('nip', 'like', $term)->orWhere('email', 'like', $term);
            });
        }

        return [
            'pengguna' => $query->orderByDesc('created_at')->paginate(10),
            'total' => [
                'verifikator' => User::where('role', RoleUser::Verifikator)->count(),
                'pemohon' => User::where('role', RoleUser::Pemohon)->count(),
                'belumAktif' => User::where('role', '!=', RoleUser::Admin->value)->whereNull('email_verified_at')->count(),
            ],
        ];
    }
};

?>

<div class="space-y-5">
    <div>
        <h1 class="text-xl font-semibold text-gray-800">Kelola Pengguna</h1>
        <p class="text-sm text-gray-500">Kelola akun verifikator dan pemohon.</p>
    </div>

    @if (session('ok'))
        <x-toast :message="session('ok')" />
    @endif

    {{-- Ringkasan --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs text-gray-400">Total Verifikator</p>
            <p class="mt-1 text-2xl font-bold text-primary-700">{{ $total['verifikator'] }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs text-gray-400">Total Pemohon</p>
            <p class="mt-1 text-2xl font-bold text-primary-700">{{ $total['pemohon'] }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs text-gray-400">Belum Aktif</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ $total['belumAktif'] }}</p>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama, NIP, atau email..."
                class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            <select wire:model.live="filterRole"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="semua">Semua Peran</option>
                <option value="verifikator">Verifikator</option>
                <option value="pemohon">Pemohon</option>
            </select>
            <select wire:model.live="filterStatus"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="semua">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="belum">Belum Aktif</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama / NIP</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Peran</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Riwayat</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pengguna as $u)
                        @php
                            $punyaRiwayat = $u->hasRiwayat();
                        @endphp
                        <tr wire:key="{{ $u->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $u->nama_lengkap }}</div>
                                <div class="font-mono text-xs text-gray-400">{{ $u->nip }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $u->email }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200">
                                    {{ $u->role->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->isAktif())
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-200">Aktif</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">Belum
                                        Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($punyaRiwayat)
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-600"
                                        title="Edit terkunci karena pengguna sudah memiliki riwayat.">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Ada riwayat
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    @can('update', $u)
                                        <a href="{{ route('admin.pengguna.edit', $u->id) }}" wire:navigate
                                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                            Edit
                                        </a>
                                    @endcan

                                    @if ($u->isAktif())
                                        @can('toggleAktivasi', $u)
                                            <button
                                                @click="$dispatch('open-confirm', { message: 'Nonaktifkan akun {{ $u->nama_lengkap }}?', callback: () => $wire.nonaktifkan('{{ $u->id }}') })"
                                                class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                                Nonaktifkan
                                            </button>
                                        @endcan
                                    @else
                                        @can('toggleAktivasi', $u)
                                            <button
                                                @click="$dispatch('open-confirm', { message: 'Aktifkan akun {{ $u->nama_lengkap }}?', callback: () => $wire.aktifkan('{{ $u->id }}') })"
                                                class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary-700">
                                                Aktifkan
                                            </button>
                                        @endcan
                                    @endif

                                    @can('delete', $u)
                                        <button
                                            @click="$dispatch('open-confirm', { message: 'Hapus akun {{ $u->nama_lengkap }} secara permanen? Tindakan ini tidak dapat dibatalkan.', callback: () => $wire.hapus('{{ $u->id }}') })"
                                            class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                            Hapus
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">
                                Tidak ada pengguna yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pengguna->hasPages())
            <div class="border-t border-gray-100 p-4">{{ $pengguna->links() }}</div>
        @endif
    </div>
</div>
