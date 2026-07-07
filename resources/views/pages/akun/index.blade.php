<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filter = 'belum'; // semua | aktif | belum

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function aktifkan(string $id): void
    {
        abort_unless(auth()->user()->isVerifikator() || auth()->user()->isAdmin(), 403);

        $user = User::where('role', 'pemohon')->findOrFail($id);
        $user->aktifkan();

        session()->flash('ok', "Akun \"{$user->nama_lengkap}\" berhasil diaktifkan.");
    }

    public function nonaktifkan(string $id): void
    {
        abort_unless(auth()->user()->isVerifikator() || auth()->user()->isAdmin(), 403);

        $user = User::where('role', 'pemohon')->findOrFail($id);
        $user->nonaktifkan();

        session()->flash('ok', "Akun \"{$user->nama_lengkap}\" berhasil dinonaktifkan.");
    }

    public function with(): array
    {
        $query = User::query()->where('role', 'pemohon');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('nama_lengkap', 'like', '%' . $this->search . '%')->orWhere('nip', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filter === 'aktif') {
            $query->whereNotNull('email_verified_at');
        } elseif ($this->filter === 'belum') {
            $query->whereNull('email_verified_at');
        }

        return [
            'pemohonList' => $query->orderByDesc('created_at')->paginate(10),
            'jumlahBelum' => User::where('role', 'pemohon')->whereNull('email_verified_at')->count(),
        ];
    }
};

?>

<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Aktivasi Akun Pemohon</h1>
            <p class="text-sm text-gray-500">Aktifkan akun pemohon yang baru mendaftar.</p>
        </div>
        @if ($jumlahBelum > 0)
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                {{ $jumlahBelum }} akun menunggu aktivasi
            </span>
        @endif
    </div>

    @if (session('ok'))
        <x-toast :message="session('ok')" />
    @endif

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4">
            <div class="relative flex-1 min-w-[200px]">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau NIP..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <select wire:model.live="filter"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="belum">Belum Aktif</option>
                <option value="aktif">Aktif</option>
                <option value="semua">Semua</option>
            </select>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama / NIP</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Instansi</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pemohonList as $p)
                        <tr wire:key="{{ $p->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $p->nama_lengkap }}</div>
                                <div class="text-xs text-gray-400">{{ $p->nip }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->email }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->instansi ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($p->isAktif())
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-200">Aktif</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">Belum
                                        Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($p->isAktif())
                                    <button
                                        @click="$dispatch('open-confirm', { message: 'Nonaktifkan akun {{ $p->nama_lengkap }}? Pemohon tidak akan bisa login.', callback: () => $wire.nonaktifkan('{{ $p->id }}') })"
                                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                        Nonaktifkan
                                    </button>
                                @else
                                    <button
                                        @click="$dispatch('open-confirm', { message: 'Aktifkan akun {{ $p->nama_lengkap }}?', callback: () => $wire.aktifkan('{{ $p->id }}') })"
                                        class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary-700">
                                        Aktifkan
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">
                                Tidak ada akun pemohon yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($pemohonList->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $pemohonList->links() }}
            </div>
        @endif
    </div>
</div>
